<?php
/**
 * Cron: geocode customers that still lack coordinates (free, OpenStreetMap).
 *
 * Respects Nominatim's ~1 request/second policy. Run every 15 minutes:
 *   *​/15 * * * * php /path/to/cron/geocode.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/console.php';

use App\Core\Database;
use App\Services\GeoService;

$db = Database::instance();
$rows = $db->all(
    "SELECT id, address, postal_code, city, country FROM customers
     WHERE latitude IS NULL AND (city <> '' OR address <> '') LIMIT 40"
);

$done = 0;
foreach ($rows as $c) {
    $address = trim(($c['address'] ?? '') . ', ' . ($c['postal_code'] ?? '') . ' ' . ($c['city'] ?? '') . ', ' . ($c['country'] ?? 'België'));
    $geo = GeoService::geocode($address);
    if ($geo !== null) {
        $db->update('customers', ['latitude' => $geo['lat'], 'longitude' => $geo['lon']], ['id' => $c['id']]);
        $done++;
    }
    sleep(1); // Nominatim rate limit.
}

echo "Geocoded {$done}/" . count($rows) . " customers.\n";
