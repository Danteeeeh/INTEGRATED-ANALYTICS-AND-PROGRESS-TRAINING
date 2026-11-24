<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Hosting health check (HostForge points at /up). It answers before the
// framework boots, so the probe stays meaningful even when the app is in
// maintenance or cannot reach its database.
if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) === '/up') {
    http_response_code(204);
    header('Cache-Control: no-store');
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
