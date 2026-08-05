<?php
/**
 * Outbound webhook dispatcher. Posts JSON payloads to registered endpoints for
 * a given event, signed with an HMAC-SHA256 signature the receiver can verify.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

final class WebhookService
{
    public static function dispatch(string $event, array $payload): void
    {
        try {
            $hooks = Database::instance()->all('SELECT * FROM webhooks WHERE event = ? AND active = 1', [$event]);
        } catch (\Throwable) {
            return;
        }
        if ($hooks === []) {
            return;
        }
        $body = json_encode(['event' => $event, 'data' => $payload, 'timestamp' => time()], JSON_UNESCAPED_UNICODE);

        foreach ($hooks as $hook) {
            $signature = hash_hmac('sha256', (string) $body, (string) ($hook['secret'] ?? ''));
            try {
                $ch = curl_init($hook['url']);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $body,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'X-SalesFlow-Event: ' . $event,
                        'X-SalesFlow-Signature: sha256=' . $signature,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 8,
                ]);
                curl_exec($ch);
                curl_close($ch);
            } catch (\Throwable $e) {
                Logger::error('Webhook dispatch failed: ' . $e->getMessage(), ['url' => $hook['url']]);
            }
        }
    }
}
