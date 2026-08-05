<?php
/**
 * Application configuration.
 *
 * Values are read from environment variables when available (VPS / production)
 * and fall back to sensible defaults or values defined in a local .env file so
 * the app also runs on shared hosting like InfinityFree where env vars are
 * limited. Never commit real secrets — copy .env.example to .env.
 */

declare(strict_types=1);

use App\Core\Env;

$root = dirname(__DIR__);

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'SalesFlow Enterprise'),
        'env'      => Env::get('APP_ENV', 'production'),
        'debug'    => Env::bool('APP_DEBUG', false),
        'url'      => rtrim(Env::get('APP_URL', 'http://localhost'), '/'),
        'timezone' => Env::get('APP_TIMEZONE', 'Europe/Brussels'),
        'locale'   => Env::get('APP_LOCALE', 'nl'),
        'key'      => Env::get('APP_KEY', ''), // 32+ char secret used for signing
        'root'     => $root,
    ],

    'database' => [
        'host'     => Env::get('DB_HOST', 'localhost'),
        'port'     => (int) Env::get('DB_PORT', '3306'),
        'name'     => Env::get('DB_NAME', 'salesflow'),
        'user'     => Env::get('DB_USER', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
    ],

    'session' => [
        'name'          => 'salesflow_session',
        'lifetime'      => (int) Env::get('SESSION_LIFETIME', '7200'),   // seconds of inactivity
        'remember_days' => (int) Env::get('REMEMBER_DAYS', '30'),
        'secure'        => Env::bool('SESSION_SECURE', false),
        'same_site'     => 'Lax',
    ],

    'security' => [
        'bcrypt_cost'        => 12,
        'max_login_attempts' => 5,
        'lockout_seconds'    => 900,
        'password_min'       => 10,
    ],

    'mail' => [
        'driver'    => Env::get('MAIL_DRIVER', 'smtp'), // smtp | gmail
        'host'      => Env::get('MAIL_HOST', 'localhost'),
        'port'      => (int) Env::get('MAIL_PORT', '587'),
        'username'  => Env::get('MAIL_USERNAME', ''),
        'password'  => Env::get('MAIL_PASSWORD', ''),
        'encryption'=> Env::get('MAIL_ENCRYPTION', 'tls'),
        'from_email'=> Env::get('MAIL_FROM', 'no-reply@salesflow.app'),
        'from_name' => Env::get('MAIL_FROM_NAME', 'SalesFlow Enterprise'),
    ],

    'integrations' => [
        'google' => [
            'client_id'     => Env::get('GOOGLE_CLIENT_ID', ''),
            'client_secret' => Env::get('GOOGLE_CLIENT_SECRET', ''),
            'redirect'      => Env::get('GOOGLE_REDIRECT', ''),
            'maps_key'      => Env::get('GOOGLE_MAPS_KEY', ''),
        ],
        'microsoft' => [
            'client_id'     => Env::get('MS_CLIENT_ID', ''),
            'client_secret' => Env::get('MS_CLIENT_SECRET', ''),
            'tenant'        => Env::get('MS_TENANT', 'common'),
            'redirect'      => Env::get('MS_REDIRECT', ''),
        ],
        'kbo' => [
            // Belgian Crossroads Bank for Enterprises lookup (cbeapi.be).
            'url' => rtrim(Env::get('KBO_API_URL', 'https://cbeapi.be/api'), '/'),
            'key' => Env::get('KBO_API_KEY', ''),
            // Override paths here if the provider changes them (no code change needed).
            'enterprise_path' => Env::get('KBO_ENTERPRISE_PATH', '/v1/enterprise/{number}'),
            'search_path'     => Env::get('KBO_SEARCH_PATH', '/v1/search?query={query}'),
        ],
    ],

    'paths' => [
        'root'    => $root,
        'app'     => $root . '/app',
        'views'   => $root . '/app/Views',
        'storage' => $root . '/storage',
        'uploads' => $root . '/storage/uploads',
        'logs'    => $root . '/storage/logs',
        'cache'   => $root . '/storage/cache',
    ],
];
