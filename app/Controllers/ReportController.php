<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\StatsService;

final class ReportController extends Controller
{
    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('reports.view', $request);
        $stats = new StatsService();
        $db = Database::instance();

        // Per-employee performance for the current month.
        $employees = $db->all(
            "SELECT u.id, u.name, u.color,
                (SELECT COUNT(*) FROM activities a WHERE a.user_id=u.id AND a.type='call' AND DATE_FORMAT(a.occurred_at,'%Y-%m')=?) AS calls,
                (SELECT COUNT(*) FROM activities a WHERE a.user_id=u.id AND a.type='email' AND DATE_FORMAT(a.occurred_at,'%Y-%m')=?) AS emails,
                (SELECT COUNT(*) FROM agenda_events e WHERE e.user_id=u.id AND e.type IN('meeting','visit') AND DATE_FORMAT(e.starts_at,'%Y-%m')=?) AS meetings,
                (SELECT COUNT(*) FROM customers c WHERE c.owner_id=u.id AND c.pipeline_stage='won' AND DATE_FORMAT(c.updated_at,'%Y-%m')=?) AS won,
                (SELECT COALESCE(SUM(c.estimated_value),0) FROM customers c WHERE c.owner_id=u.id AND c.pipeline_stage='won' AND DATE_FORMAT(c.updated_at,'%Y-%m')=?) AS revenue
             FROM users u WHERE u.status='active' ORDER BY revenue DESC",
            array_fill(0, 5, date('Y-m'))
        );

        $this->view('reports/index', [
            'title'      => 'Rapporten',
            'overview'   => $stats->overview(null),
            'conversion' => $stats->conversionRate(null),
            'pipeline'   => $stats->pipeline(null),
            'revenue'    => $stats->revenueTrend(12),
            'activity'   => $stats->activityTrend(14, null),
            'employees'  => $employees,
        ]);
    }

    /** CSV export (opens directly in Excel — free, no libraries). */
    public function export(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $this->authorize('reports.view', $request);
        $type = (string) ($params['type'] ?? 'customers');
        $db = Database::instance();

        [$filename, $header, $rows] = match ($type) {
            'activities' => [
                'activiteiten',
                ['Datum', 'Type', 'Onderwerp', 'Klant', 'Gebruiker', 'Duur (s)'],
                $db->all("SELECT a.occurred_at, a.type, a.subject, c.company_name, u.name AS user_name, a.duration_seconds
                          FROM activities a LEFT JOIN customers c ON c.id=a.customer_id LEFT JOIN users u ON u.id=a.user_id
                          ORDER BY a.occurred_at DESC LIMIT 5000"),
            ],
            'quotations' => [
                'offertes',
                ['Nummer', 'Klant', 'Titel', 'Totaal', 'Status', 'Datum'],
                $db->all("SELECT q.number, c.company_name, q.title, q.total, q.status, q.created_at
                          FROM quotations q JOIN customers c ON c.id=q.customer_id ORDER BY q.created_at DESC LIMIT 5000"),
            ],
            default => [
                'klanten',
                ['Bedrijf', 'Stad', 'Fase', 'Status', 'Eigenaar', 'Waarde', 'Laatste contact'],
                $db->all("SELECT c.company_name, c.city, c.pipeline_stage, c.status, u.name AS owner, c.estimated_value, c.last_contact_at
                          FROM customers c LEFT JOIN users u ON u.id=c.owner_id ORDER BY c.company_name LIMIT 5000"),
            ],
        };

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="salesflow-' . $filename . '-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel.
        fputcsv($out, $header, ';');
        foreach ($rows as $row) {
            fputcsv($out, array_values($row), ';');
        }
        fclose($out);
        exit;
    }
}
