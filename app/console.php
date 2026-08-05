<?php
/**
 * CLI bootstrap for cron jobs and scripts. Loads the autoloader, environment,
 * configuration and helpers — without sessions, headers or routing.
 */

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Config;
use App\Core\Env;

$root = dirname(__DIR__);
require $root . '/app/Core/Autoloader.php';

$autoloader = new Autoloader();
$autoloader->addNamespace('App', $root . '/app');
$autoloader->register();

require $root . '/app/Core/helpers.php';

Env::load($root . '/.env');
Config::load(require $root . '/config/config.php');

date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));
