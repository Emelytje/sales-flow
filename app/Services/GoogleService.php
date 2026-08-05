<?php
/**
 * Google OAuth 2.0 + Gmail API client.
 *
 * Handles the authorization-code flow, token storage/refresh (oauth_tokens) and
 * reading the user's Gmail inbox. All methods degrade gracefully when Google is
 * not configured, so the app runs unchanged without it. Uses gmail.readonly +
 * gmail.send scopes; only activates once GOOGLE_CLIENT_ID/SECRET are set.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;

final class GoogleService
{
    private const AUTH  = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN = 'https://oauth2.googleapis.com/token';
    private const GMAIL = 'https://gmail.googleapis.com/gmail/v1/users/me';
    private const SCOPES = 'https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/userinfo.email';

    public static function configured(): bool
    {
        return (string) Config::get('integrations.google.client_id') !== ''
            && (string) Config::get('integrations.google.client_secret') !== '';
    }

    public static function connected(int $userId): bool
    {
        return Database::instance()->first('SELECT id FROM oauth_tokens WHERE user_id = ? AND provider = "google"', [$userId]) !== null;
    }

    /** Build the consent-screen URL. State should be a CSRF-bound random value. */
    public static function authUrl(string $state): string
    {
        return self::AUTH . '?' . http_build_query([
            'client_id'     => (string) Config::get('integrations.google.client_id'),
            'redirect_uri'  => (string) Config::get('integrations.google.redirect'),
            'response_type' => 'code',
            'scope'         => self::SCOPES,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);
    }

    /** Exchange an authorization code for tokens and persist them. */
    public static function handleCallback(int $userId, string $code): bool
    {
        $resp = self::post(self::TOKEN, [
            'code'          => $code,
            'client_id'     => (string) Config::get('integrations.google.client_id'),
            'client_secret' => (string) Config::get('integrations.google.client_secret'),
            'redirect_uri'  => (string) Config::get('integrations.google.redirect'),
            'grant_type'    => 'authorization_code',
        ]);
        if (!isset($resp['access_token'])) {
            Logger::error('Google token exchange failed', $resp);
            return false;
        }

        $email = null;
        $info = self::get('https://www.googleapis.com/oauth2/v2/userinfo', $resp['access_token']);
        $email = $info['email'] ?? null;

        $db = Database::instance();
        $existing = $db->first('SELECT id FROM oauth_tokens WHERE user_id = ? AND provider = "google"', [$userId]);
        $row = [
            'access_token'  => $resp['access_token'],
            'refresh_token' => $resp['refresh_token'] ?? ($existing['refresh_token'] ?? null),
            'account_email' => $email,
            'scopes'        => self::SCOPES,
            'expires_at'    => date('Y-m-d H:i:s', time() + (int) ($resp['expires_in'] ?? 3600)),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        if ($existing) {
            $db->update('oauth_tokens', $row, ['id' => $existing['id']]);
        } else {
            $row['user_id'] = $userId;
            $row['provider'] = 'google';
            $row['created_at'] = date('Y-m-d H:i:s');
            $db->insert('oauth_tokens', $row);
        }
        return true;
    }

    public static function disconnect(int $userId): void
    {
        Database::instance()->delete('oauth_tokens', ['user_id' => $userId, 'provider' => 'google']);
    }

    private static function accessToken(int $userId): ?string
    {
        $row = Database::instance()->first('SELECT * FROM oauth_tokens WHERE user_id = ? AND provider = "google"', [$userId]);
        if ($row === null) {
            return null;
        }
        if ($row['expires_at'] !== null && strtotime($row['expires_at']) > time() + 60) {
            return $row['access_token'];
        }
        if (empty($row['refresh_token'])) {
            return null;
        }
        $resp = self::post(self::TOKEN, [
            'client_id'     => (string) Config::get('integrations.google.client_id'),
            'client_secret' => (string) Config::get('integrations.google.client_secret'),
            'refresh_token' => $row['refresh_token'],
            'grant_type'    => 'refresh_token',
        ]);
        if (!isset($resp['access_token'])) {
            return null;
        }
        Database::instance()->update('oauth_tokens', [
            'access_token' => $resp['access_token'],
            'expires_at'   => date('Y-m-d H:i:s', time() + (int) ($resp['expires_in'] ?? 3600)),
            'updated_at'   => date('Y-m-d H:i:s'),
        ], ['id' => $row['id']]);
        return $resp['access_token'];
    }

    /**
     * List recent inbox messages (headers + snippet).
     * @return array<int, array{id:string, from:string, subject:string, snippet:string, date:string, unread:bool}>
     */
    public static function inbox(int $userId, int $max = 20): array
    {
        $token = self::accessToken($userId);
        if ($token === null) {
            return [];
        }
        $list = self::get(self::GMAIL . '/messages?maxResults=' . $max . '&labelIds=INBOX', $token);
        $result = [];
        foreach ($list['messages'] ?? [] as $ref) {
            $msg = self::get(self::GMAIL . '/messages/' . $ref['id'] . '?format=metadata&metadataHeaders=From&metadataHeaders=Subject&metadataHeaders=Date', $token);
            $headers = [];
            foreach ($msg['payload']['headers'] ?? [] as $h) {
                $headers[strtolower($h['name'])] = $h['value'];
            }
            $result[] = [
                'id'      => (string) $ref['id'],
                'from'    => $headers['from'] ?? '',
                'subject' => $headers['subject'] ?? '(geen onderwerp)',
                'snippet' => html_entity_decode((string) ($msg['snippet'] ?? ''), ENT_QUOTES),
                'date'    => $headers['date'] ?? '',
                'unread'  => in_array('UNREAD', $msg['labelIds'] ?? [], true),
            ];
        }
        return $result;
    }

    /** Fetch one message's decoded body (prefers text/html). */
    public static function message(int $userId, string $id): ?array
    {
        $token = self::accessToken($userId);
        if ($token === null) {
            return null;
        }
        $msg = self::get(self::GMAIL . '/messages/' . $id . '?format=full', $token);
        if (!isset($msg['payload'])) {
            return null;
        }
        $headers = [];
        foreach ($msg['payload']['headers'] ?? [] as $h) {
            $headers[strtolower($h['name'])] = $h['value'];
        }
        return [
            'from'    => $headers['from'] ?? '',
            'to'      => $headers['to'] ?? '',
            'subject' => $headers['subject'] ?? '',
            'date'    => $headers['date'] ?? '',
            'body'    => self::sanitizeHtml(self::extractBody($msg['payload'])),
        ];
    }

    /**
     * Basic HTML sanitizer for untrusted inbound email bodies: removes script/
     * style/iframe/object blocks, inline event handlers and javascript: URIs so
     * a malicious email can't run code in the CRM origin.
     */
    private static function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed|link|meta)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|iframe|object|embed|link|meta)\b[^>]*/?>#is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
        $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*(\2)/i', '$1="#"', $html) ?? $html;
        return $html;
    }

    /** Recursively pull the best body part from a Gmail payload. */
    private static function extractBody(array $payload): string
    {
        $mime = $payload['mimeType'] ?? '';
        if (str_starts_with($mime, 'text/html') && !empty($payload['body']['data'])) {
            return self::b64($payload['body']['data']);
        }
        if (str_starts_with($mime, 'text/plain') && !empty($payload['body']['data'])) {
            return nl2br(htmlspecialchars(self::b64($payload['body']['data'])));
        }
        $htmlFallback = '';
        foreach ($payload['parts'] ?? [] as $part) {
            $body = self::extractBody($part);
            if (($part['mimeType'] ?? '') === 'text/html' && $body !== '') {
                return $body;
            }
            if ($body !== '' && $htmlFallback === '') {
                $htmlFallback = $body;
            }
        }
        return $htmlFallback;
    }

    private static function b64(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }

    /** @return array<string,mixed> */
    private static function post(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode((string) $res, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string,mixed> */
    private static function get(string $url, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $decoded = json_decode((string) $res, true);
        return is_array($decoded) ? $decoded : [];
    }
}
