<?php
/**
 * Web Push delivery using VAPID (RFC 8292) authentication.
 *
 * Sends "tickle" push messages (no encrypted payload) which wake the service
 * worker; the worker then shows the notification and the app syncs the latest
 * content on open. This keeps delivery reliable and dependency-free while
 * remaining fully standards-compliant. Requires a VAPID key pair — generate one
 * with `php cron/generate_vapid.php` and put the keys in .env.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

final class WebPushService
{
    public static function configured(): bool
    {
        return trim((string) Config::get('integrations.google.maps_key', '')) !== '' || (
            getenv('VAPID_PUBLIC_KEY') && getenv('VAPID_PRIVATE_KEY')
        );
    }

    public static function sendToUser(int $userId, string $title, string $body, string $url = '/notifications'): void
    {
        $public = (string) getenv('VAPID_PUBLIC_KEY');
        $privatePem = self::privateKeyPem();
        if ($public === '' || $privatePem === null) {
            return; // Push not configured — silently skip.
        }

        $subs = Database::instance()->all('SELECT * FROM push_subscriptions WHERE user_id = ?', [$userId]);
        foreach ($subs as $sub) {
            try {
                self::deliver($sub, $public, $privatePem, ['title' => $title, 'body' => $body, 'url' => $url]);
            } catch (\Throwable $e) {
                Logger::error('Web push failed: ' . $e->getMessage(), ['endpoint' => $sub['endpoint']]);
            }
        }
    }

    private static function deliver(array $sub, string $publicKey, string $privatePem, array $data): void
    {
        $endpoint = $sub['endpoint'];
        $parts = parse_url($endpoint);
        $audience = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        $jwt = self::vapidJwt($audience, $privatePem);

        $headers = [
            'Authorization: vapid t=' . $jwt . ', k=' . $publicKey,
            'Crypto-Key: p256ecdsa=' . $publicKey,
            'TTL: 86400',
            'Content-Length: 0',
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 404/410 → subscription expired, remove it.
        if (in_array($code, [404, 410], true)) {
            Database::instance()->delete('push_subscriptions', ['id' => $sub['id']]);
        }
    }

    /** Build and sign an ES256 VAPID JWT. */
    private static function vapidJwt(string $audience, string $privatePem): string
    {
        $header = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $payload = self::b64(json_encode([
            'aud' => $audience,
            'exp' => time() + 12 * 3600,
            'sub' => (string) getenv('VAPID_SUBJECT') ?: 'mailto:admin@salesflow.app',
        ]));
        $signingInput = $header . '.' . $payload;

        $key = openssl_pkey_get_private($privatePem);
        if ($key === false) {
            throw new \RuntimeException('Invalid VAPID private key.');
        }
        openssl_sign($signingInput, $derSignature, $key, OPENSSL_ALGO_SHA256);

        // Convert DER ECDSA signature to raw R||S (64 bytes) for JWS.
        $raw = self::derToRaw($derSignature);
        return $signingInput . '.' . self::b64($raw);
    }

    private static function privateKeyPem(): ?string
    {
        $val = (string) getenv('VAPID_PRIVATE_KEY');
        if ($val === '') {
            return null;
        }
        // Accept a PEM directly, or a base64-encoded PEM (single-line in .env).
        if (str_contains($val, 'BEGIN')) {
            return $val;
        }
        $decoded = base64_decode($val, true);
        return $decoded !== false && str_contains($decoded, 'BEGIN') ? $decoded : null;
    }

    /** DER-encoded ECDSA signature → fixed 64-byte R||S. */
    private static function derToRaw(string $der): string
    {
        $offset = 0;
        $readInt = static function (string $der, int &$offset): string {
            $offset++; // skip 0x02 tag
            $len = ord($der[$offset++]);
            $val = substr($der, $offset, $len);
            $offset += $len;
            $val = ltrim($val, "\x00");
            return str_pad($val, 32, "\x00", STR_PAD_LEFT);
        };
        $offset += 2; // skip sequence tag + length
        $r = $readInt($der, $offset);
        $s = $readInt($der, $offset);
        return $r . $s;
    }

    public static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
