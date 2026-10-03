<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$healthzRequestUri = $_SERVER['REQUEST_URI'] ?? '';
$healthzPath = parse_url($healthzRequestUri, PHP_URL_PATH) ?: $healthzRequestUri;
if ($healthzPath === '/up' || $healthzPath === '/healthz' || $healthzPath === '/health' || $healthzPath === '/ping') {
    header('HTTP/1.1 204 No Content', true, 204);
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Healthz-Probe: standalone-v1');
    header('X-Content-Type-Options: nosniff');
    if (function_exists('fastcgi_finish_request') && PHP_SAPI === 'fpm-fcgi') {
        fastcgi_finish_request();
    }
    exit(0);
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
