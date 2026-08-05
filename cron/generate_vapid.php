<?php
/**
 * Generate a VAPID key pair for Web Push and print the .env lines.
 *
 * Usage:  php cron/generate_vapid.php
 * Copy the printed VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY into your .env.
 */

declare(strict_types=1);

$res = openssl_pkey_new([
    'private_key_type' => OPENSSL_KEYTYPE_EC,
    'curve_name'       => 'prime256v1',
]);
if ($res === false) {
    fwrite(STDERR, "Failed to generate EC key (is OpenSSL enabled?).\n");
    exit(1);
}

openssl_pkey_export($res, $privatePem);
$details = openssl_pkey_get_details($res);

// Uncompressed public point: 0x04 || X || Y  → base64url (application server key).
$x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
$y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
$publicRaw = "\x04" . $x . $y;
$b64url = static fn (string $d): string => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');

$publicKey = $b64url($publicRaw);
$privateB64 = base64_encode($privatePem); // single-line for .env

echo "\n# --- Web Push (VAPID) — add these to your .env ---\n";
echo 'VAPID_PUBLIC_KEY=' . $publicKey . "\n";
echo 'VAPID_PRIVATE_KEY=' . $privateB64 . "\n";
echo "VAPID_SUBJECT=mailto:admin@your-domain.tld\n\n";
echo "Public key (for the frontend applicationServerKey):\n" . $publicKey . "\n";
