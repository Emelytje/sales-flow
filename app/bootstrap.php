<?php
/**
 * Application bootstrap.
 *
 * Registers the autoloader, loads environment + configuration, starts the
 * session and returns a configured Router ready to dispatch.
 */

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\Env;
use App\Core\Logger;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

define('APP_START', microtime(true));
$root = dirname(__DIR__);

require $root . '/app/Core/Autoloader.php';

$autoloader = new Autoloader();
$autoloader->addNamespace('App', $root . '/app');
$autoloader->register();

require $root . '/app/Core/helpers.php';

// Environment + configuration.
Env::load($root . '/.env');
Config::load(require $root . '/config/config.php');

date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));

// Error handling.
$debug = (bool) Config::get('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');

set_exception_handler(static function (\Throwable $e) use ($debug): void {
    Logger::error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    http_response_code(500);
    if (($_SERVER['HTTP_ACCEPT'] ?? '') && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        Response::json(['error' => $debug ? $e->getMessage() : 'Interne serverfout.'], 500);
    }
    if ($debug) {
        echo '<pre style="padding:2rem;font-family:monospace;">'
            . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString())
            . '</pre>';
    } else {
        try {
            echo View::render('errors/500', ['title' => 'Er ging iets mis'], 'layouts/blank');
        } catch (\Throwable) {
            echo 'Er ging iets mis.';
        }
    }
    exit;
});

// Security headers.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');
header_remove('X-Powered-By');

Session::start();

$router = new Router();

// Load route definitions.
(require $root . '/routes/web.php')($router);
(require $root . '/routes/api.php')($router);

return $router;
