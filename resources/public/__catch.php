<?php

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

header_register_callback(function () {
    if (! headers_sent()) {
        header_remove('X-Frame-Options');
        header_remove('Strict-Transport-Security');
    }
});

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    die('Method Not Allowed');
}

$sig = $_GET['sig'] ?? $_SERVER['HTTP_X_CATCH_SIG'] ?? null;
$pwd = $_GET['pwd'] ?? $_SERVER['HTTP_X_CATCH_PWD'] ?? null;

$markerFile = __DIR__.'/../storage/framework/__catch_enable.txt';
$markerFilePresent = is_file($markerFile);

if ($markerFilePresent) {
    $sigOk = true;
} elseif ($pwd !== null) {
    $appKeyFallback = $_ENV['APP_KEY'] ?? $_SERVER['APP_KEY'] ?? '';
    if ($appKeyFallback === '' && is_file(__DIR__.'/../.env')) {
        $lines = @file(__DIR__.'/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), 'APP_KEY=') && str_contains($line, '=')) {
                    [, $val] = explode('=', $line, 2);
                    $appKeyFallback = trim($val);
                    break;
                }
            }
        }
    }
    $pwdExpected = hash_hmac('sha256', 'catch-pwd::LMS::HostForge-2026', (string)$appKeyFallback);
    $sigOk = hash_equals($pwdExpected, (string)$pwd) || hash_equals(substr($pwdExpected,0,32), (string)$pwd);
} elseif ($sig !== null) {
    if ($appKey === '') {
        http_response_code(503);
        die('APP_KEY not readable.');
    }

    $expected    = hash_hmac('sha256', 'catch-debug:'.gmdate('YmdH'), $appKey);
    $expectedAlt = hash_hmac('sha256', 'catch-debug:'.gmdate('YmdH', time() - 3600), $appKey);
    $sigOk = hash_equals($expected, (string)$sig) || hash_equals($expectedAlt, (string)$sig);
} else {
    http_response_code(400);
    echo "ERROR: No auth provided. Choose ONE of:\n";
    echo "  (a) ?sig= — HMAC hash_hmac('sha256','catch-debug:'.gmdate('YmdH'),APP_KEY)\n";
    echo "  (b) ?pwd= — HMAC hash_hmac('sha256','catch-pwd::LMS::HostForge-2026',APP_KEY) (first 32 chars also accepted)\n";
    echo "  (c) Create file storage/framework/__catch_enable.txt inside container (touch via release command or committed)\n";
    die();
}

if ($appKey === '') {
    $appKey = $_ENV['APP_KEY'] ?? $_SERVER['APP_KEY'] ?? '';
    if ($appKey === '' && is_file(__DIR__.'/../.env')) {
        $lines = @file(__DIR__.'/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), 'APP_KEY=') && str_contains($line, '=')) {
                    [, $val] = explode('=', $line, 2);
                    $appKey = trim($val);
                    $_ENV['APP_KEY'] = $appKey;
                    $_SERVER['APP_KEY'] = $appKey;
                    break;
                }
            }
        }
    }
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('html_errors', '1');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) {
    if (! headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8', true, 500);
    }
    echo '<style>body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;margin:24px;color:#111}h1{color:#b00020}h2{color:#333;margin-top:32px}.panel{background:#fff4f5;border:1px solid #f5c2c7;border-radius:8px;padding:16px;margin:12px 0}.box{background:#f8f9fa;border:1px solid #dee2e6;border-radius:8px;padding:14px;overflow:auto;max-height:500px;font-family:ui-monospace,Consolas,monospace;font-size:12px;white-space:pre-wrap;word-break:break-word}.kv{display:grid;grid-template-columns:auto 1fr;gap:6px 16px;margin:8px 0}.kv div{padding:4px 0;border-bottom:1px dashed #eee}.muted{color:#666;font-size:12px}</style>';
    echo '<h1>Uncaught Exception</h1>';
    $i = 0;
    $cur = $e;
    while ($cur !== null) {
        $i++;
        echo '<div class="panel">';
        echo '<h2>'.($i > 1 ? 'Previous #'.($i - 1).': ' : '').htmlspecialchars(get_class($cur)).'</h2>';
        echo '<div class="kv">';
        echo '<div class="muted">Message</div><div><strong>'.htmlspecialchars($cur->getMessage()).'</strong></div>';
        echo '<div class="muted">Code</div><div>'.(int)$cur->getCode().'</div>';
        echo '<div class="muted">File</div><div>'.htmlspecialchars($cur->getFile()).'</div>';
        echo '<div class="muted">Line</div><div>'.(int)$cur->getLine().'</div>';
        echo '</div>';
        echo '<h3>Trace</h3>';
        echo '<div class="box">'.htmlspecialchars($cur->getTraceAsString()).'</div>';
        echo '</div>';
        $cur = $cur->getPrevious();
    }

    echo '<h2>Runtime Environment</h2>';
    echo '<div class="box">';
    echo 'PHP_VERSION       = '.PHP_VERSION."\n";
    echo 'PHP_SAPI          = '.PHP_SAPI."\n";
    echo 'DOCUMENT_ROOT     = '.($_SERVER['DOCUMENT_ROOT'] ?? 'N/A')."\n";
    echo 'REQUEST_URI       = '.($_SERVER['REQUEST_URI'] ?? 'N/A')."\n";
    echo 'REQUEST_METHOD    = '.($_SERVER['REQUEST_METHOD'] ?? 'N/A')."\n";
    echo 'HTTPS (raw)       = '.var_export(! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', true)."\n";
    echo 'SERVER_PORT       = '.($_SERVER['SERVER_PORT'] ?? 'N/A')."\n";
    echo 'X-Forwarded-Proto = '.($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'N/A')."\n";
    echo 'X-Forwarded-Host  = '.($_SERVER['HTTP_X_FORWARDED_HOST'] ?? 'N/A')."\n";
    echo 'X-Forwarded-For   = '.($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'N/A')."\n";
    echo 'X-Forwarded-Ssl   = '.($_SERVER['HTTP_X_FORWARDED_SSL'] ?? 'N/A')."\n";
    echo 'Front-End-Https   = '.($_SERVER['HTTP_FRONT_END_HTTPS'] ?? 'N/A')."\n";
    echo 'CF-Connecting-IP  = '.($_SERVER['HTTP_CF_CONNECTING_IP'] ?? 'N/A')."\n";
    echo 'CWD               = '.getcwd()."\n";
    echo 'APP_KEY present   = '.(isset($_ENV['APP_KEY']) && $_ENV['APP_KEY'] !== '' ? 'YES ('.$_ENV['APP_KEY'].')' : 'NO')."\n";
    echo "--- Included files before boot ---\n";
    foreach (get_included_files() as $k => $f) {
        echo '  ['.$k.'] '.$f."\n";
    }
    echo '</div>';

    exit(1);
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if (! headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8', true, 500);
    }
    echo '<h1 style="color:#b00020;">PHP Error ['.$severity.']</h1>';
    echo '<p><strong>'.htmlspecialchars($message).'</strong></p>';
    echo '<p>File: '.htmlspecialchars($file).':'.$line.'</p>';
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    array_shift($bt);
    $out = '';
    foreach ($bt as $i => $f) {
        $out .= '#'.$i.' '.($f['file'] ?? '?').':'.($f['line'] ?? '?').' → '.($f['class'] ?? '').($f['type'] ?? '').($f['function'] ?? '?')."\n";
    }
    echo '<pre style="max-height:500px;overflow:auto;background:#f4f4f4;padding:10px;">'.htmlspecialchars($out).'</pre>';
    exit(1);
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatal = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (! in_array($err['type'], $fatal, true)) {
        return;
    }
    if (! headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8', true, 500);
    }
    echo '<h1 style="color:#b00020;">Fatal Shutdown Error ['.$err['type'].']</h1>';
    echo '<p><strong>'.htmlspecialchars($err['message']).'</strong></p>';
    echo '<p>File: '.htmlspecialchars($err['file']).':'.$err['line'].'</p>';
});

echo '<h2>Pre-boot checks</h2><ul>';
echo '<li>Autoload: '.is_file(__DIR__.'/../vendor/autoload.php').'</li>';
echo '<li>bootstrap/app.php: '.is_file(__DIR__.'/../bootstrap/app.php').'</li>';
echo '</ul>';

try {
    define('LARAVEL_START', microtime(true));
    require __DIR__.'/../vendor/autoload.php';

    if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
        echo '<p class="muted">Maintenance file present — not requiring it for this diagnostic.</p>';
    }

    echo '<h2>Booting Laravel $app...</h2>';
    $t0 = microtime(true);
    $app = require __DIR__.'/../bootstrap/app.php';
    $dtBoot = (microtime(true) - $t0) * 1000;
    $envDisplay = env('APP_ENV', '(unknown via env())') ?? 'n/a';
    try {
        if (method_exists($app, 'environmentFile')) {
            $envDisplay = $app['config']->get('app.env', $envDisplay);
        }
    } catch (\Throwable) { }
    echo '<p>App booted in <strong>'.number_format($dtBoot, 1).'ms</strong>. Env = '.var_export($envDisplay, true).'. Debug = '.var_export($app['config']->get('app.debug'), true).'. APP_URL = '.htmlspecialchars($app['config']->get('app.url')).'</p>';

    echo '<h3>Env/config snapshot</h3>';
    echo '<div class="box" style="font-size:11px;">';
    echo 'DB_CONNECTION = ' . $app['config']->get('database.default')."\n";
    echo 'DB_HOST       = ' . $app['config']->get('database.connections.'.$app['config']->get('database.default').'.host')."\n";
    echo 'DB_DATABASE   = ' . $app['config']->get('database.connections.'.$app['config']->get('database.default').'.database')."\n";
    echo 'SESSION_DRIVER= ' . $app['config']->get('session.driver')."\n";
    echo 'SESSION_SECURE= ' . var_export($app['config']->get('session.secure'), true)."\n";
    echo 'CACHE_DEFAULT = ' . $app['config']->get('cache.default')."\n";
    echo 'TRUSTED PROXIES= ' . var_export($app['config']->get('trustedproxy.proxies'), true)."\n";
    echo 'APP_DEBUG     = ' . var_export($app['config']->get('app.debug'), true)."\n";
    echo 'APP_ENV       = ' . $app['config']->get('app.env')."\n";
    echo '</div>';

    echo '<h2>Making HTTP Kernel...</h2>';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo '<h3>Global middleware list</h3>';
    echo '<div class="box" style="font-size:11px;">';
    foreach ($kernel->getMiddleware() as $idx => $m) {
        echo '['.$idx.'] '.$m."\n";
    }
    echo '</div>';
    echo '<h3>Web middleware group</h3>';
    echo '<div class="box" style="font-size:11px;">';
    foreach ($kernel->getMiddlewareGroups()['web'] ?? [] as $idx => $m) {
        if (is_array($m)) {
            echo '['.$idx.'] '.json_encode($m, JSON_UNESCAPED_SLASHES)."\n";
        } else {
            echo '['.$idx.'] '.$m."\n";
        }
    }
    echo '</div>';

    $targets = ['/', '/login', '/up', '/healthz', '/diagnostic'];
    echo '<h2>Simulating requests</h2>';
    foreach ($targets as $uri) {
        echo '<h3>GET '.$uri.'</h3>';
        try {
            $fakeRequest = Illuminate\Http\Request::create($uri, 'GET', [], [], [], [
                'HTTP_HOST'            => 'online-learning.bcpsms2.com',
                'HTTP_USER_AGENT'      => 'CatchDebug/1.0',
                'REMOTE_ADDR'          => '10.0.0.1',
                'HTTP_X_FORWARDED_PROTO'=> 'https',
                'HTTP_X_FORWARDED_FOR' => '10.1.2.3',
                'HTTP_X_FORWARDED_HOST'=> 'online-learning.bcpsms2.com',
            ]);
            $r0 = microtime(true);
            ob_start();
            $response = $kernel->handle($fakeRequest);
            $body = ob_get_clean();
            $dt = (microtime(true) - $r0) * 1000;
            echo '<p>Status = <strong>'.$response->getStatusCode().'</strong> in '.number_format($dt, 1).'ms; header Content-Type = '.($response->headers->get('content-type') ?? '?').'; content len (via getContent) = '.strlen($response->getContent()).' bytes; ob buffer len = '.strlen($body).'</p>';
            if ($response->isRedirection()) {
                echo '<p>Redirect → '.htmlspecialchars($response->headers->get('location') ?? '').'</p>';
            } elseif ($response->getStatusCode() >= 400) {
                echo '<h4>Response body (first 3000 chars)</h4>';
                echo '<div class="box" style="font-size:11px;">'.htmlspecialchars(substr($response->getContent() ?: $body, 0, 3000)).'</div>';
            }
            $app->terminate($fakeRequest, $response);
        } catch (Throwable $innerEx) {
            echo '<div class="panel">';
            echo '<h2>Inner exception for '.$uri.' → '.htmlspecialchars(get_class($innerEx)).'</h2>';
            echo '<p><strong>'.htmlspecialchars($innerEx->getMessage()).'</strong></p>';
            echo '<p>File: '.htmlspecialchars($innerEx->getFile()).':'.$innerEx->getLine().'</p>';
            echo '<div class="box" style="font-size:11px;">'.htmlspecialchars($innerEx->getTraceAsString()).'</div>';
            echo '</div>';
        }
    }

    echo '<h2>✅ All simulated requests handled without uncaught exception</h2>';
} catch (Throwable $eOuter) {
    header('Content-Type: text/html; charset=UTF-8', true, 500);
    echo '<h1 style="color:#b00020;">BOOT EXCEPTION</h1>';
    echo '<p>Caught at top-level try/catch.</p>';
    echo '<div class="panel"><h2>'.htmlspecialchars(get_class($eOuter)).'</h2>';
    echo '<p><strong>'.htmlspecialchars($eOuter->getMessage()).'</strong></p>';
    echo '<p>File: '.htmlspecialchars($eOuter->getFile()).':'.$eOuter->getLine().'</p>';
    echo '<div class="box">'.htmlspecialchars($eOuter->getTraceAsString()).'</div>';
    $prev = $eOuter->getPrevious(); $i=0;
    while ($prev !== null) { $i++;
        echo '<h3>Previous #'.$i.': '.htmlspecialchars(get_class($prev)).'</h3>';
        echo '<p><strong>'.htmlspecialchars($prev->getMessage()).'</strong></p>';
        echo '<p>File: '.htmlspecialchars($prev->getFile()).':'.$prev->getLine().'</p>';
        echo '<div class="box" style="font-size:11px;">'.htmlspecialchars($prev->getTraceAsString()).'</div>';
        $prev = $prev->getPrevious();
    }
    echo '</div>';
}
