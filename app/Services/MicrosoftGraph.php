<?php
/**
 * Thin Microsoft Graph client. Uses a user's stored OAuth token (oauth_tokens)
 * and refreshes it when expired. All methods no-op gracefully when the
 * integration is not configured, so the app runs without Microsoft set up.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

final class MicrosoftGraph
{
    private const BASE = 'https://graph.microsoft.com/v1.0';

    public static function configured(): bool
    {
        return (string) Config::get('integrations.microsoft.client_id') !== ''
            && (string) Config::get('integrations.microsoft.client_secret') !== '';
    }

    /** Return a valid access token for the user, refreshing if needed. */
    public static function tokenFor(int $userId): ?string
    {
        $row = Database::instance()->first(
            'SELECT * FROM oauth_tokens WHERE user_id = ? AND provider = "microsoft"',
            [$userId]
        );
        if ($row === null) {
            return null;
        }
        if ($row['expires_at'] !== null && strtotime($row['expires_at']) > time() + 60) {
            return $row['access_token'];
        }
        return self::refresh($userId, $row);
    }

    private static function refresh(int $userId, array $row): ?string
    {
        if (empty($row['refresh_token'])) {
            return null;
        }
        $tenant = (string) Config::get('integrations.microsoft.tenant', 'common');
        $resp = self::http('POST', "https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
            'client_id'     => (string) Config::get('integrations.microsoft.client_id'),
            'client_secret' => (string) Config::get('integrations.microsoft.client_secret'),
            'grant_type'    => 'refresh_token',
            'refresh_token' => $row['refresh_token'],
            'scope'         => 'offline_access User.Read OnlineMeetings.ReadWrite Calendars.ReadWrite',
        ], true);

        if (!isset($resp['access_token'])) {
            Logger::error('MS token refresh failed', ['user' => $userId]);
            return null;
        }
        Database::instance()->update('oauth_tokens', [
            'access_token'  => $resp['access_token'],
            'refresh_token' => $resp['refresh_token'] ?? $row['refresh_token'],
            'expires_at'    => date('Y-m-d H:i:s', time() + (int) ($resp['expires_in'] ?? 3600)),
            'updated_at'    => date('Y-m-d H:i:s'),
        ], ['id' => $row['id']]);
        return $resp['access_token'];
    }

    /** @return array<string,mixed>|null */
    public static function post(int $userId, string $path, array $body): ?array
    {
        $token = self::tokenFor($userId);
        if ($token === null) {
            return null;
        }
        return self::http('POST', self::BASE . $path, $body, false, ['Authorization: Bearer ' . $token]);
    }

    /**
     * @param array<string,mixed> $data
     * @param array<int,string> $headers
     * @return array<string,mixed>
     */
    private static function http(string $method, string $url, array $data, bool $form = false, array $headers = []): array
    {
        $ch = curl_init($url);
        $payload = $form ? http_build_query($data) : json_encode($data);
        $headers[] = $form ? 'Content-Type: application/x-www-form-urlencoded' : 'Content-Type: application/json';
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode((string) $res, true);
        return is_array($decoded) ? $decoded : [];
    }
}
