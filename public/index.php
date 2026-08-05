<?php
/**
 * Front controller — the single public entry point.
 *
 * Works with two layouts automatically:
 *   1. VPS / recommended: web root points at /public, with /app one level above.
 *   2. InfinityFree / shared hosting: everything lives in htdocs together, so
 *      /app sits next to this file. We locate bootstrap.php in either place.
 */

declare(strict_types=1);

use App\Core\Request;

$candidates = [
    __DIR__ . '/app/bootstrap.php',          // flat layout (htdocs)
    dirname(__DIR__) . '/app/bootstrap.php',  // public/ web root
];

$bootstrap = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $bootstrap = $candidate;
        break;
    }
}

if ($bootstrap === null) {
    http_response_code(500);
    exit('Kan app/bootstrap.php niet vinden. Controleer de mapstructuur.');
}

/** @var App\Core\Router $router */
$router = require $bootstrap;

$router->dispatch(new Request());
