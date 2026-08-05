<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class SearchController extends Controller
{
    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $q = trim((string) $request->query('q', ''));
        $results = ['customers' => [], 'contacts' => [], 'quotations' => []];

        if ($q !== '') {
            $db = Database::instance();
            $like = '%' . $q . '%';
            $results['customers'] = $db->all(
                'SELECT id, company_name, city, pipeline_stage FROM customers
                 WHERE company_name LIKE ? OR email LIKE ? OR vat_number LIKE ? OR city LIKE ? LIMIT 15',
                [$like, $like, $like, $like]
            );
            $results['contacts'] = $db->all(
                'SELECT ct.id, ct.first_name, ct.last_name, ct.email, ct.customer_id, cu.company_name
                 FROM contacts ct JOIN customers cu ON cu.id = ct.customer_id
                 WHERE ct.first_name LIKE ? OR ct.last_name LIKE ? OR ct.email LIKE ? LIMIT 15',
                [$like, $like, $like]
            );
            $results['quotations'] = $db->all(
                'SELECT q.id, q.number, q.title, q.total, q.status, cu.company_name
                 FROM quotations q JOIN customers cu ON cu.id = q.customer_id
                 WHERE q.number LIKE ? OR q.title LIKE ? OR cu.company_name LIKE ? LIMIT 15',
                [$like, $like, $like]
            );
        }

        if ($request->wantsJson()) {
            $this->json($results);
        }
        $this->view('search/index', ['title' => 'Zoeken', 'q' => $q, 'results' => $results]);
    }
}
