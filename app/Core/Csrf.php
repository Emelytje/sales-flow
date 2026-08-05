<?php
/**
 * CSRF token generation and verification (double-submit + session token).
 */

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::KEY)) {
            Session::set(self::KEY, bin2hex(random_bytes(32)));
        }
        return (string) Session::get(self::KEY);
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    public static function verify(?string $token): bool
    {
        $stored = Session::get(self::KEY);
        if (!is_string($stored) || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($stored, $token);
    }
}
