<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Quotation;

final class QuotationApiController extends Controller
{
    public function index(Request $request): never
    {
        $this->json((new Quotation())->listing(
            (int) $request->query('page', '1'),
            min(100, (int) $request->query('per_page', '25')),
            trim((string) $request->query('q', '')),
            $request->query('status')
        ));
    }

    public function show(Request $request, array $params): never
    {
        $q = (new Quotation())->detailed((int) $params['id']);
        if ($q === null) {
            $this->json(['error' => 'Niet gevonden'], 404);
        }
        $q['items'] = (new Quotation())->items((int) $params['id']);
        $this->json($q);
    }
}
