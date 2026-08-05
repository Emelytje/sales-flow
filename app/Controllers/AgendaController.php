<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Event;
use App\Services\MeetingService;

final class AgendaController extends Controller
{
    private Event $events;

    public function __construct()
    {
        $this->events = new Event();
    }

    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('agenda.view', $request);
        $this->view('agenda/index', [
            'title'     => 'Agenda',
            'customers' => Database::instance()->all('SELECT id, company_name FROM customers ORDER BY company_name LIMIT 500'),
            'team'      => Database::instance()->all("SELECT id, name, color FROM users WHERE status='active' ORDER BY name"),
            'me'        => (int) Auth::id(),
        ]);
    }

    /** JSON feed for the calendar. */
    public function events(Request $request): never
    {
        $this->requireAuth($request);
        $start = (string) $request->query('start', date('Y-m-01'));
        $end = (string) $request->query('end', date('Y-m-t'));

        // Optional colleague filter (comma-separated user ids).
        $ownerFilter = [];
        $usersParam = (string) $request->query('users', '');
        if ($usersParam !== '') {
            $ownerFilter = array_filter(array_map('intval', explode(',', $usersParam)));
        }

        $rows = $this->events->inRange((int) Auth::id(), $start . ' 00:00:00', $end . ' 23:59:59', $ownerFilter);
        $typeColor = ['meeting' => '#7A6FF0', 'call' => '#2FA36B', 'task' => '#D98A2B', 'visit' => '#4C9AA6', 'vacation' => '#9C8F98', 'other' => '#E98CAB'];

        $this->json(['events' => array_map(static function (array $e) use ($typeColor): array {
            return [
                'id'       => (int) $e['id'],
                'title'    => $e['title'],
                'start'    => $e['starts_at'],
                'end'      => $e['ends_at'],
                'allDay'   => (bool) $e['all_day'],
                'color'    => $e['color'] ?: ($typeColor[$e['type']] ?? '#E98CAB'),
                'type'     => $e['type'],
                'location' => $e['location'],
                'company'  => $e['company_name'],
                'owner'    => $e['owner_name'],
                'owner_id' => (int) $e['user_id'],
                'owner_color' => $e['owner_color'] ?: '#E98CAB',
                'customer_id' => $e['customer_id'] ? (int) $e['customer_id'] : null,
                'description' => $e['description'],
                'travel'   => (int) $e['travel_minutes'],
                'teams'    => $e['teams_join_url'],
                'visibility' => $e['visibility'],
                'mine'     => (int) $e['user_id'] === (int) Auth::id(),
            ];
        }, $rows)]);
    }

    public function store(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('agenda.manage', $request);
        $this->verifyCsrf($request);

        $data = $this->validate($request, [
            'title'     => 'required|max:190',
            'starts_at' => 'required|date',
            'ends_at'   => 'required|date',
        ], ['title' => 'Titel', 'starts_at' => 'Start', 'ends_at' => 'Einde']);

        $payload = [
            'user_id'        => Auth::id(),
            'customer_id'    => $request->input('customer_id') ?: null,
            'title'          => $data['title'],
            'description'    => $request->input('description'),
            'type'           => in_array($request->input('type'), ['meeting', 'call', 'task', 'visit', 'vacation', 'other'], true) ? $request->input('type') : 'meeting',
            'location'       => $request->input('location'),
            'starts_at'      => date('Y-m-d H:i:s', strtotime((string) $data['starts_at'])),
            'ends_at'        => date('Y-m-d H:i:s', strtotime((string) $data['ends_at'])),
            'all_day'        => (int) (bool) $request->input('all_day'),
            'travel_minutes' => (int) $request->input('travel_minutes', 0),
            'visibility'     => $request->input('visibility') === 'private' ? 'private' : 'shared',
            'reminder_minutes' => (int) $request->input('reminder_minutes', 30),
        ];

        // Optional online meeting — free by default (Jitsi), Teams if connected.
        if ($request->input('create_meeting') || $request->input('create_teams')) {
            $meeting = MeetingService::create(
                (int) Auth::id(),
                (string) $data['title'],
                $payload['starts_at'],
                $payload['ends_at'],
                (bool) $request->input('prefer_teams')
            );
            $payload['teams_join_url'] = $meeting['url'];
        }

        $conflict = $this->events->hasConflict((int) Auth::id(), $payload['starts_at'], $payload['ends_at']);
        $id = $this->events->create($payload);

        $this->json(['ok' => true, 'id' => $id, 'conflict' => $conflict]);
    }

    public function update(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('agenda.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];

        $event = $this->events->find($id);
        if ($event === null || (int) $event['user_id'] !== (int) Auth::id()) {
            $this->json(['error' => 'Niet toegestaan.'], 403);
        }

        $update = [];
        foreach (['title', 'description', 'location', 'type', 'visibility'] as $f) {
            if ($request->input($f) !== null) {
                $update[$f] = $request->input($f);
            }
        }
        if ($request->input('starts_at')) {
            $update['starts_at'] = date('Y-m-d H:i:s', strtotime((string) $request->input('starts_at')));
        }
        if ($request->input('ends_at')) {
            $update['ends_at'] = date('Y-m-d H:i:s', strtotime((string) $request->input('ends_at')));
        }
        if ($request->input('status')) {
            $update['status'] = $request->input('status');
        }
        $this->events->update($id, $update);
        $this->json(['ok' => true]);
    }

    public function destroy(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('agenda.manage', $request);
        $this->verifyCsrf($request);
        $id = (int) $params['id'];
        $event = $this->events->find($id);
        if ($event && (int) $event['user_id'] === (int) Auth::id()) {
            $this->events->delete($id);
        }
        $this->json(['ok' => true]);
    }
}
