<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\GeoService;

final class MapController extends Controller
{
    public function index(Request $request): never
    {
        $this->requireAuth($request);
        $this->authorize('map.view', $request);

        $customers = Database::instance()->all(
            "SELECT id, company_name, address, postal_code, city, phone, pipeline_stage,
                    latitude, longitude
             FROM customers
             WHERE company_name <> '' ORDER BY company_name LIMIT 1000"
        );

        $this->view('map/index', [
            'title'     => 'Kaart & routes',
            'customers' => $customers,
            'missing'   => count(array_filter($customers, static fn ($c) => $c['latitude'] === null)),
        ]);
    }

    /** Geocode a customer's address (free, OpenStreetMap) and store coords. */
    public function geocode(Request $request, array $params): never
    {
        $this->requireAuth($request);
        $id = (int) $params['id'];
        $db = Database::instance();
        $c = $db->first('SELECT * FROM customers WHERE id = ?', [$id]);
        if ($c === null) {
            $this->json(['error' => 'Niet gevonden'], 404);
        }
        $address = trim(($c['address'] ?? '') . ', ' . ($c['postal_code'] ?? '') . ' ' . ($c['city'] ?? '') . ', ' . ($c['country'] ?? 'België'));
        $geo = GeoService::geocode($address);
        if ($geo === null) {
            $this->json(['ok' => false, 'error' => 'Adres niet gevonden.']);
        }
        $db->update('customers', ['latitude' => $geo['lat'], 'longitude' => $geo['lon'], 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        $this->json(['ok' => true, 'lat' => $geo['lat'], 'lon' => $geo['lon']]);
    }
}
