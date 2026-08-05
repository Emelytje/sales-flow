<?php
/**
 * Free geocoding + EU VAT validation.
 *
 *  - Geocoding via OpenStreetMap Nominatim (free, no API key).
 *  - VAT validation via the EU VIES REST service (free).
 *
 * Both are optional helpers; failures return null and never break the flow.
 * Nominatim usage policy: max ~1 req/sec and a descriptive User-Agent.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

final class GeoService
{
    /**
     * Geocode a free-form address to coordinates.
     * @return array{lat:float, lon:float, display:string}|null
     */
    public static function geocode(string $address): ?array
    {
        $address = trim($address);
        if ($address === '') {
            return null;
        }
        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' . rawurlencode($address);
        $body = self::get($url, ['User-Agent: SalesFlowEnterprise/1.0 (CRM geocoder)']);
        $data = json_decode((string) $body, true);
        if (!is_array($data) || !isset($data[0]['lat'])) {
            return null;
        }
        return [
            'lat'     => (float) $data[0]['lat'],
            'lon'     => (float) $data[0]['lon'],
            'display' => (string) ($data[0]['display_name'] ?? $address),
        ];
    }

    /**
     * Validate an EU VAT number via VIES (free).
     * @return array{valid:bool, name?:string, address?:string}|null
     */
    public static function checkVat(string $vat): ?array
    {
        $vat = strtoupper(preg_replace('/[^A-Z0-9]/', '', $vat) ?? '');
        if (strlen($vat) < 4) {
            return null;
        }
        $country = substr($vat, 0, 2);
        $number = substr($vat, 2);
        $url = "https://ec.europa.eu/taxation_customs/vies/rest-api/ms/{$country}/vat/{$number}";
        $body = self::get($url, ['Accept: application/json']);
        $data = json_decode((string) $body, true);
        if (!is_array($data) || !isset($data['isValid'])) {
            return null;
        }
        return [
            'valid'   => (bool) $data['isValid'],
            'name'    => $data['name'] ?? null,
            'address' => $data['address'] ?? null,
        ];
    }

    private static function get(string $url, array $headers = []): ?string
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $res = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $code >= 200 && $code < 300 ? (string) $res : null;
        } catch (\Throwable $e) {
            Logger::error('GeoService request failed: ' . $e->getMessage());
            return null;
        }
    }
}
