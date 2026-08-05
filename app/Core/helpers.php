<?php
/**
 * Global helper functions available in controllers and views.
 */

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Icons;
use App\Core\Session;

if (!function_exists('icon')) {
    function icon(string $name, int $size = 24, string $stroke = '2'): string
    {
        return Icons::get($name, $size, $stroke);
    }
}

if (!function_exists('e')) {
    /** Escape a string for safe HTML output (XSS protection). */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = (string) Config::get('app.url', '');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('auth')) {
    function auth(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return Auth::can($permission);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = Session::get('_flash')['old'] ?? [];
        return $old[$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    /** @return array<string, array<int, string>> */
    function errors(): array
    {
        return Session::get('_flash')['errors'] ?? [];
    }
}

if (!function_exists('error')) {
    function error(string $field): ?string
    {
        return errors()[$field][0] ?? null;
    }
}

if (!function_exists('money')) {
    function money(float|int|string $amount, string $currency = '€'): string
    {
        return $currency . ' ' . number_format((float) $amount, 2, ',', '.');
    }
}

if (!function_exists('date_nl')) {
    function date_nl(?string $date, string $format = 'd/m/Y'): string
    {
        if ($date === null || $date === '' || $date === '0000-00-00 00:00:00') {
            return '—';
        }
        $ts = strtotime($date);
        return $ts ? date($format, $ts) : '—';
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $date): string
    {
        if (!$date) {
            return '—';
        }
        $ts = strtotime($date);
        if (!$ts) {
            return '—';
        }
        $diff = time() - $ts;
        if ($diff < 60) {
            return 'zojuist';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' min geleden';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' u geleden';
        }
        if ($diff < 604800) {
            return floor($diff / 86400) . ' d geleden';
        }
        return date('d/m/Y', $ts);
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1) : '';
        return mb_strtoupper($first . $last);
    }
}
