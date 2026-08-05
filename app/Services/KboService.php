<?php
/**
 * Belgian company lookup via the CBE/KBO API (cbeapi.be).
 *
 * Resolves a company by its enterprise/VAT number or by name and returns a
 * normalized shape the customer form can auto-fill. Field mapping is defensive
 * (accepts several possible key names) and endpoint paths are configurable, so
 * the integration keeps working if the provider tweaks its schema.
 *
 * Requires KBO_API_KEY in .env. Falls back to the free VIES VAT check (via
 * GeoService) when no KBO key is set but a VAT number is given.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;

final class KboService
{
    public static function configured(): bool
    {
        return (string) Config::get('integrations.kbo.key', '') !== '';
    }

    /** Normalize to 10 digits (KBO enterprise number). */
    public static function normalizeNumber(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        // A Belgian VAT "BE0123456789" → 0123456789; enterprise numbers are 10 digits.
        if (strlen($digits) === 9) {
            $digits = '0' . $digits;
        }
        return $digits;
    }

    /**
     * Look up a single enterprise by KBO/VAT number.
     * @return array<string,mixed>|null normalized company data, or null.
     */
    public static function byNumber(string $number): ?array
    {
        $num = self::normalizeNumber($number);
        if (strlen($num) !== 10) {
            return null;
        }

        if (self::configured()) {
            $path = str_replace('{number}', $num, (string) Config::get('integrations.kbo.enterprise_path'));
            $data = self::request((string) Config::get('integrations.kbo.url') . $path);
            if ($data !== null) {
                return self::normalize($data);
            }
        }

        // Fallback: free VIES VAT validation (name + address when available).
        $vies = GeoService::checkVat('BE' . $num);
        if ($vies !== null && $vies['valid']) {
            return [
                'company_name' => $vies['name'] ?? '',
                'vat_number'   => 'BE' . $num,
                'kbo_number'   => $num,
                'address'      => $vies['address'] ?? '',
                'source'       => 'VIES',
            ];
        }
        return null;
    }

    /**
     * Search companies by name.
     * @return array<int, array<string,mixed>>
     */
    public static function searchByName(string $name): array
    {
        if (!self::configured() || trim($name) === '') {
            return [];
        }
        $path = str_replace('{query}', rawurlencode(trim($name)), (string) Config::get('integrations.kbo.search_path'));
        $data = self::request((string) Config::get('integrations.kbo.url') . $path);
        if ($data === null) {
            return [];
        }
        $rows = $data['data'] ?? $data['results'] ?? $data['enterprises'] ?? (array_is_list($data) ? $data : []);
        return array_map(static fn ($r) => self::normalize($r), array_slice($rows, 0, 10));
    }

    /** Map a provider record to our field names, tolerating key variations. */
    private static function normalize(array $r): array
    {
        // Some APIs nest the record under "enterprise" or "data".
        $r = $r['enterprise'] ?? $r['data'] ?? $r;

        $name = $r['denomination'] ?? $r['name'] ?? $r['company_name'] ?? '';
        if (is_array($name)) {
            $name = $name['value'] ?? $name['nl'] ?? $name['fr'] ?? reset($name) ?: '';
        }

        $addr = $r['address'] ?? $r['registered_office'] ?? $r['seat'] ?? [];
        if (is_string($addr)) {
            $street = $addr;
            $zip = '';
            $city = '';
        } else {
            $street = trim(($addr['street'] ?? $addr['street_name'] ?? '') . ' ' . ($addr['house_number'] ?? $addr['number'] ?? ''));
            $zip = (string) ($addr['zipcode'] ?? $addr['zip'] ?? $addr['postal_code'] ?? '');
            $city = (string) ($addr['city'] ?? $addr['municipality'] ?? $addr['town'] ?? '');
            if (is_array($city)) {
                $city = $city['nl'] ?? $city['value'] ?? reset($city) ?: '';
            }
        }

        $cbe = (string) ($r['cbe_number'] ?? $r['enterprise_number'] ?? $r['number'] ?? '');
        $cbeDigits = preg_replace('/\D/', '', $cbe) ?? '';

        return [
            'company_name' => (string) $name,
            'kbo_number'   => $cbeDigits,
            'vat_number'   => $cbeDigits !== '' ? 'BE' . $cbeDigits : '',
            'address'      => $street,
            'postal_code'  => $zip,
            'city'         => $city,
            'status'       => (string) ($r['status'] ?? ''),
            'juridical_form' => (string) (is_array($r['juridical_form'] ?? null) ? ($r['juridical_form']['description'] ?? '') : ($r['juridical_form'] ?? '')),
            'source'       => 'KBO',
        ];
    }

    /** @return array<string,mixed>|null */
    private static function request(string $url): ?array
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . (string) Config::get('integrations.kbo.key'),
                    'Accept: application/json',
                ],
            ]);
            $res = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code < 200 || $code >= 300) {
                Logger::info('KBO lookup non-2xx', ['url' => $url, 'code' => $code]);
                return null;
            }
            $decoded = json_decode((string) $res, true);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Logger::error('KBO request failed: ' . $e->getMessage());
            return null;
        }
    }
}
