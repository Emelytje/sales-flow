<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\StatsService;

final class DashboardController extends Controller
{
    /** Landing route: send guests to login, users to the dashboard. */
    public function root(Request $request): never
    {
        $this->redirect(Auth::check() ? '/dashboard' : '/login');
    }

    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $stats = new StatsService();
        $userId = Auth::id();
        $isManager = Auth::isAdmin() || Auth::hasRole('manager');

        // Managers/admins see team-wide figures; sales see their own.
        $scope = $isManager ? null : $userId;

        $db = Database::instance();
        $today = date('Y-m-d');

        $schedule = $db->all(
            "SELECT e.*, c.company_name FROM agenda_events e
             LEFT JOIN customers c ON c.id = e.customer_id
             WHERE e.user_id = ? AND DATE(e.starts_at) = ?
             ORDER BY e.starts_at ASC",
            [$userId, $today]
        );

        $tasks = $db->all(
            "SELECT * FROM tasks WHERE user_id = ? AND status = 'open'
             ORDER BY (due_at IS NULL), due_at ASC LIMIT 6",
            [$userId]
        );

        $this->view('dashboard/index', [
            'title'       => 'Dashboard',
            'overview'    => $stats->overview($scope),
            'conversion'  => $stats->conversionRate($scope),
            'pipeline'    => $stats->pipeline($scope),
            'revenue'     => $stats->revenueTrend(6),
            'activity'    => $stats->activityTrend(7, $scope),
            'leaderboard' => $stats->leaderboard(5),
            'feed'        => $stats->recentActivity(10),
            'schedule'    => $schedule,
            'tasks'       => $tasks,
            'isManager'   => $isManager,
        ]);
    }
}
