<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\GeoService;
use App\Services\KboService;

final class LookupController extends Controller
{
    /** KBO/VAT number → company details (auto-fill). */
    public function kbo(Request $request): never
    {
        $this->requireAuth($request);
        $number = (string) $request->query('number', '');
        if ($number === '') {
            $this->json(['error' => 'Geef een KBO- of BTW-nummer op.'], 422);
        }
        $result = KboService::byNumber($number);
        if ($result === null) {
            $this->json(['found' => false, 'error' => 'Geen gegevens gevonden.']);
        }
        $this->json(['found' => true, 'company' => $result]);
    }

    /** Search companies by name. */
    public function kboSearch(Request $request): never
    {
        $this->requireAuth($request);
        $q = (string) $request->query('q', '');
        $results = KboService::searchByName($q);
        $this->json([
            'configured' => KboService::configured(),
            'results'    => $results,
        ]);
    }

    /** Free VIES VAT validation. */
    public function vat(Request $request): never
    {
        $this->requireAuth($request);
        $vat = (string) $request->query('vat', '');
        $result = GeoService::checkVat($vat);
        if ($result === null) {
            $this->json(['valid' => false, 'error' => 'Kon BTW-nummer niet controleren.']);
        }
        $this->json($result);
    }
}
