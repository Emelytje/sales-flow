<?php
/**
 * Front controller — the single public entry point.
 *
 * On a VPS point the document root here (/public). On InfinityFree, place this
 * file's directory as htdocs and keep /app, /config, etc. one level above the
 * web root (or protect them with the shipped .htaccess rules).
 */

declare(strict_types=1);

use App\Core\Request;

/** @var App\Core\Router $router */
$router = require dirname(__DIR__) . '/app/bootstrap.php';

$router->dispatch(new Request());
