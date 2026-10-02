<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// -------------------------------------------------------------------------
// Friendly boot-time error pages (HostForge deploy helpers)
// Without these, a missing vendor/ or empty APP_KEY gives a confusing blank
// 500 white screen. Instead, show the user exactly what went wrong.
// -------------------------------------------------------------------------

if (PHP_SAPI !== 'cli') {
    $bootErrors = [];

    // 1. Composer vendor/autoload missing?  (build step failed or not run)
    $autoload = __DIR__.'/../vendor/autoload.php';
    if (! file_exists($autoload)) {
        $bootErrors[] = (object) [
            'title'   => 'Composer dependencies not installed',
            'details' => 'vendor/autoload.php could not be found. The build step either did not run or failed before completing `composer install`.',
            'run'     => 'composer install --no-dev --optimize-autoloader --no-interaction',
        ];
    }

    // 2. APP_KEY empty?  (Laravel's default boot path still works but the
    //    EncryptionServiceProvider throws on first encrypt/decrypt — warn
    //    early so admins notice.)
    $appKey = $_ENV['APP_KEY'] ?? $_SERVER['APP_KEY'] ?? '';
    if (empty($appKey) && function_exists('getenv')) {
        $appKey = getenv('APP_KEY') ?: '';
    }
    if (empty($appKey) && file_exists(__DIR__.'/../.env')) {
        // Dotenv will load below. Suppress static-env false negative.
        $appKey = $appKey ?: 'CHECK_DOTENV';
    }
    if (empty($appKey)) {
        $bootErrors[] = (object) [
            'title'   => 'APP_KEY is not set',
            'details' => 'Laravel cannot encrypt/decrypt session/cookie data, forms, or auth credentials without a base64 application key.',
            'run'     => 'php artisan key:generate --show   (paste the full base64:xxxxxx output into APP_KEY in HostForge env vars)',
        ];
    }

    if (! empty($bootErrors) && PHP_SAPI !== 'cli') {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">',
             '<meta name="viewport" content="width=device-width,initial-scale=1">',
             '<title>Boot Error — LMS Deployment Check</title>',
             '<style>body{font-family:ui-sans-serif,system-ui,Segoe UI,sans-serif;margin:0;background:#f8fafc;color:#0f172a}.wrap{max-width:760px;margin:40px auto;padding:24px}h1{font-size:22px;color:#991b1b;margin:0 0 12px}.card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 20px;margin:14px 0;box-shadow:0 1px 2px rgba(15,23,42,.04)}.card h2{font-size:16px;color:#7c2d12;margin:0 0 6px}.card p{margin:6px 0;font-size:14px;color:#334155}.code{background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:12.5px;color:#1e293b;white-space:pre-wrap;overflow-x:auto;margin-top:10px}.tip{background:#eff6ff;border-left:3px solid #2563eb;padding:12px 14px;margin-top:18px;color:#1e3a8a;border-radius:0 6px 6px 0;font-size:13.5px}</style></head><body><div class="wrap"><h1>⚠️  Application boot errors (deploy checklist)</h1><p>Fix each item below, then redeploy the application.</p>';
        foreach ($bootErrors as $e) {
            echo '<div class="card"><h2>',htmlspecialchars($e->title),'</h2><p>',htmlspecialchars($e->details),'</p>',
                 '<div class="code">',htmlspecialchars($e->run),'</div></div>';
        }
        echo '<div class="tip"><strong>HostForge tip:</strong> open the <em>Technical Logs</em> tab in this deployment for additional output. If the health check keeps failing even after this page appears, change the Health Check endpoint in HostForge to <code>/healthz.php</code> or <code>/up.php</code> — these are standalone probes that bypass Laravel entirely so the container is allowed to stay running while you fix Laravel.</div></div></body></html>';
        exit(1);
    }

    unset($autoload, $appKey);
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
