<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Activity;
use App\Services\StatsService;

final class CallboardController extends Controller
{
    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('callboard.use', $request);

        $user = Auth::user();
        $goal = (int) ($user['daily_call_goal'] ?? 40);
        $callsToday = (int) Database::instance()->scalar(
            "SELECT COUNT(*) FROM activities WHERE user_id = ? AND type='call' AND DATE(occurred_at)=?",
            [Auth::id(), date('Y-m-d')]
        );

        $this->view('callboard/index', [
            'title'       => 'Callboard',
            'customer'    => $this->nextCustomer(),
            'goal'        => $goal,
            'calls_today' => $callsToday,
            'leaderboard' => (new StatsService())->leaderboard(5),
        ]);
    }

    /** AJAX: fetch the next customer to call as JSON. */
    public function next(Request $request): never
    {
        $this->requireAuth($request);
        $exclude = (int) $request->query('exclude', '0');
        $customer = $this->nextCustomer($exclude);
        $this->json(['customer' => $customer]);
    }

    /**
     * Record a call outcome, log the activity, and schedule follow-up.
     */
    public function outcome(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('callboard.use', $request);
        $this->verifyCsrf($request);

        $customerId = (int) $request->input('customer_id');
        $outcome = (string) $request->input('outcome', 'called');
        $duration = (int) $request->input('duration', 0);
        $note = trim((string) $request->input('note', ''));

        $db = Database::instance();
        $customer = $db->first('SELECT * FROM customers WHERE id = ?', [$customerId]);
        if ($customer === null) {
            $this->json(['error' => 'Klant niet gevonden'], 404);
        }

        // Map outcome → activity type + pipeline / follow-up effects.
        $map = [
            'called'         => ['call', 'Gebeld', null, null],
            'no_answer'      => ['call', 'Geen gehoor', null, '+1 day'],
            'emailed'        => ['email', 'E-mail verstuurd', null, null],
            'follow_up'      => ['call', 'Opvolgen', 'contacted', '+3 days'],
            'appointment'    => ['call', 'Afspraak gemaakt', 'qualified', null],
            'interested'     => ['call', 'Geïnteresseerd', 'qualified', '+2 days'],
            'not_interested' => ['call', 'Niet geïnteresseerd', 'lost', null],
            'quotation'      => ['call', 'Offerte gevraagd', 'proposal', '+1 day'],
            'customer'       => ['call', 'Nieuwe klant!', 'won', null],
        ];
        [$type, $label, $newStage, $followUp] = $map[$outcome] ?? $map['called'];

        (new Activity())->log([
            'customer_id'      => $customerId,
            'user_id'          => Auth::id(),
            'type'             => $type,
            'subject'          => $label,
            'body'             => $note,
            'outcome'          => $outcome,
            'duration_seconds' => $duration > 0 ? $duration : null,
        ]);

        $update = ['updated_at' => date('Y-m-d H:i:s'), 'last_contact_at' => date('Y-m-d H:i:s')];
        if ($newStage !== null) {
            $update['pipeline_stage'] = $newStage;
            if ($newStage === 'won') {
                $update['status'] = 'customer';
            }
            if ($newStage === 'lost') {
                $update['lost_reason'] = $note ?: 'Niet geïnteresseerd (callboard)';
            }
        }
        if ($followUp !== null) {
            $update['next_action_at'] = date('Y-m-d H:i:s', strtotime($followUp));
        } else {
            $update['next_action_at'] = null;
        }
        $db->update('customers', $update, ['id' => $customerId]);

        $this->json([
            'ok'          => true,
            'calls_today' => (int) $db->scalar("SELECT COUNT(*) FROM activities WHERE user_id=? AND type='call' AND DATE(occurred_at)=?", [Auth::id(), date('Y-m-d')]),
            'next'        => $this->nextCustomer($customerId),
        ]);
    }

    public function leaderboard(Request $request): never
    {
        $this->requireAuth($request);
        $this->json(['leaderboard' => (new StatsService())->leaderboard(10)]);
    }

    /**
     * Pick the most deserving customer to call next: due follow-ups first,
     * then high priority, then least-recently contacted open leads owned by
     * (or unassigned to) the current user.
     */
    private function nextCustomer(int $exclude = 0): ?array
    {
        $params = [Auth::id()];
        $excludeSql = '';
        if ($exclude > 0) {
            $excludeSql = ' AND c.id <> ?';
            $params[] = $exclude;
        }

        return Database::instance()->first(
            "SELECT c.*, s.name AS sector_name,
                    (SELECT COUNT(*) FROM contacts ct WHERE ct.customer_id = c.id) AS contact_count,
                    (SELECT ct.phone FROM contacts ct WHERE ct.customer_id = c.id AND ct.phone IS NOT NULL LIMIT 1) AS contact_phone
             FROM customers c
             LEFT JOIN sectors s ON s.id = c.sector_id
             WHERE c.pipeline_stage IN ('lead','contacted','qualified','proposal')
               AND c.status <> 'inactive'
               AND (c.owner_id = ? OR c.owner_id IS NULL)
               {$excludeSql}
             ORDER BY
               (c.next_action_at IS NOT NULL AND c.next_action_at <= NOW()) DESC,
               FIELD(c.priority,'high','medium','low'),
               (c.last_contact_at IS NULL) DESC,
               c.last_contact_at ASC
             LIMIT 1",
            $params
        );
    }
}
