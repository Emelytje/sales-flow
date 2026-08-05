<?php
/**
 * Aggregation queries powering the dashboard and reports. All queries are
 * parameterized and scoped to a user or team as needed.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class StatsService
{
    private function db(): Database
    {
        return Database::instance();
    }

    /** Overview counters for the dashboard cards. */
    public function overview(?int $userId = null): array
    {
        $db = $this->db();
        $today = date('Y-m-d');
        $scope = $userId ? ' AND user_id = ' . (int) $userId : '';
        $ownScope = $userId ? ' AND owner_id = ' . (int) $userId : '';

        return [
            'calls_today'    => (int) $db->scalar("SELECT COUNT(*) FROM activities WHERE type='call' AND DATE(occurred_at)=?{$scope}", [$today]),
            'emails_today'   => (int) $db->scalar("SELECT COUNT(*) FROM activities WHERE type='email' AND DATE(occurred_at)=?{$scope}", [$today]),
            'meetings_today' => (int) $db->scalar("SELECT COUNT(*) FROM agenda_events WHERE type IN('meeting','visit') AND DATE(starts_at)=?{$scope}", [$today]),
            'open_quotes'    => (int) $db->scalar("SELECT COUNT(*) FROM quotations WHERE status IN('sent','viewed','draft')" . ($userId ? ' AND user_id=' . (int) $userId : '')),
            'new_customers'  => (int) $db->scalar("SELECT COUNT(*) FROM customers WHERE DATE(created_at)=?{$ownScope}", [$today]),
            'pipeline_value' => (float) $db->scalar("SELECT COALESCE(SUM(estimated_value),0) FROM customers WHERE pipeline_stage NOT IN('won','lost')" . $ownScope),
            'won_value_month'=> (float) $db->scalar("SELECT COALESCE(SUM(estimated_value),0) FROM customers WHERE pipeline_stage='won' AND DATE_FORMAT(updated_at,'%Y-%m')=?" . $ownScope, [date('Y-m')]),
            'total_customers'=> (int) $db->scalar("SELECT COUNT(*) FROM customers WHERE 1" . ($userId ? ' AND owner_id=' . (int) $userId : '')),
        ];
    }

    /** Conversion rate (won / total closed) as a percentage. */
    public function conversionRate(?int $userId = null): float
    {
        $scope = $userId ? ' AND owner_id = ' . (int) $userId : '';
        $won = (int) $this->db()->scalar("SELECT COUNT(*) FROM customers WHERE pipeline_stage='won'{$scope}");
        $closed = (int) $this->db()->scalar("SELECT COUNT(*) FROM customers WHERE pipeline_stage IN('won','lost'){$scope}");
        return $closed > 0 ? round($won / $closed * 100, 1) : 0.0;
    }

    /** Pipeline counts and value per stage. */
    public function pipeline(?int $userId = null): array
    {
        $scope = $userId ? ' AND owner_id = ' . (int) $userId : '';
        $rows = $this->db()->all(
            "SELECT pipeline_stage AS stage, COUNT(*) AS cnt, COALESCE(SUM(estimated_value),0) AS value
             FROM customers WHERE 1{$scope} GROUP BY pipeline_stage"
        );
        $stages = ['lead' => [0, 0], 'contacted' => [0, 0], 'qualified' => [0, 0], 'proposal' => [0, 0], 'won' => [0, 0], 'lost' => [0, 0]];
        foreach ($rows as $r) {
            $stages[$r['stage']] = [(int) $r['cnt'], (float) $r['value']];
        }
        return $stages;
    }

    /** Revenue for the last N months (won estimated value). */
    public function revenueTrend(int $months = 6): array
    {
        $rows = $this->db()->all(
            "SELECT DATE_FORMAT(updated_at,'%Y-%m') AS ym, COALESCE(SUM(estimated_value),0) AS total
             FROM customers WHERE pipeline_stage='won' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY ym ORDER BY ym ASC",
            [$months]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[$r['ym']] = (float) $r['total'];
        }
        $labels = [];
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $labels[] = date('M', strtotime($ym . '-01'));
            $data[] = $map[$ym] ?? 0.0;
        }
        return ['labels' => $labels, 'data' => $data];
    }

    /** Activity volume (calls/emails/meetings) over the last N days. */
    public function activityTrend(int $days = 7, ?int $userId = null): array
    {
        $scope = $userId ? ' AND user_id = ' . (int) $userId : '';
        $rows = $this->db()->all(
            "SELECT DATE(occurred_at) AS d, type, COUNT(*) AS c FROM activities
             WHERE occurred_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY){$scope}
             GROUP BY d, type",
            [$days - 1]
        );
        $labels = [];
        $calls = [];
        $emails = [];
        $index = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('D', strtotime($d));
            $index[$d] = count($labels) - 1;
            $calls[] = 0;
            $emails[] = 0;
        }
        foreach ($rows as $r) {
            if (!isset($index[$r['d']])) {
                continue;
            }
            $pos = $index[$r['d']];
            if ($r['type'] === 'call') {
                $calls[$pos] = (int) $r['c'];
            } elseif ($r['type'] === 'email') {
                $emails[$pos] = (int) $r['c'];
            }
        }
        return ['labels' => $labels, 'calls' => $calls, 'emails' => $emails];
    }

    /** Sales leaderboard by calls + won value for the current month. */
    public function leaderboard(int $limit = 5): array
    {
        return $this->db()->all(
            "SELECT u.id, u.name, u.color,
                (SELECT COUNT(*) FROM activities a WHERE a.user_id=u.id AND a.type='call' AND DATE_FORMAT(a.occurred_at,'%Y-%m')=?) AS calls,
                (SELECT COUNT(*) FROM agenda_events e WHERE e.user_id=u.id AND e.type IN('meeting','visit') AND DATE_FORMAT(e.starts_at,'%Y-%m')=?) AS meetings,
                (SELECT COALESCE(SUM(c.estimated_value),0) FROM customers c WHERE c.owner_id=u.id AND c.pipeline_stage='won' AND DATE_FORMAT(c.updated_at,'%Y-%m')=?) AS revenue
             FROM users u WHERE u.status='active'
             ORDER BY revenue DESC, calls DESC LIMIT {$limit}",
            [date('Y-m'), date('Y-m'), date('Y-m')]
        );
    }

    /** Most recent activity feed entries across the team. */
    public function recentActivity(int $limit = 12): array
    {
        return $this->db()->all(
            "SELECT a.*, u.name AS user_name, u.color AS user_color, c.company_name
             FROM activities a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN customers c ON c.id = a.customer_id
             ORDER BY a.occurred_at DESC LIMIT {$limit}"
        );
    }
}
