<?php

$buildFiles = [
    'AppServiceProvider' => __DIR__.'/../app/Providers/AppServiceProvider.php',
    'BootstrapApp'       => __DIR__.'/../bootstrap/app.php',
    'PublicIndex'        => __DIR__.'/index.php',
    'SessionConfig'      => __DIR__.'/../config/session.php',
    'DatabaseConfig'     => __DIR__.'/../config/database.php',
    'WebRoutes'          => __DIR__.'/../routes/web.php',
    'Diagnostic'         => __DIR__.'/../app/Http/Controllers/DiagnosticController.php',
    'ViteManifest'       => __DIR__.'/build/manifest.json',
];

function getFingerprint($path)
{
    if (! is_file($path)) {
        return ['size' => null, 'sha256_16' => null, 'first_line' => null, 'first_200' => null, 'exists' => false];
    }
    clearstatcache(true, $path);
    $content = file_get_contents($path);
    $size = strlen($content);
    $sha256_16 = substr(hash('sha256', $content), 0, 16);
    $lines = preg_split('/\R/', rtrim($content));
    $firstLine = $lines[0] ?? '';
    $lastModified = filemtime($path);

    // Has-specific content checks: detect particular fixes
    $hasProbeV1        = str_contains($content, 'X-Healthz-Probe: standalone-v1');
    $hasConfigureProxy = str_contains($content, 'configureProxyAndScheme');
    $hasUsingRouting   = str_contains($content, 'using: function ()');
    $hasEnvHelperFix   = str_contains($content, "env('SESSION_SECURE_COOKIE', ! in_array(env('APP_ENV'");
    $hasPdoTimeout     = str_contains($content, 'ATTR_TIMEOUT');
    $hasStatelessUp    = str_contains($content, "PreventRequestsDuringMaintenance::except(['/up'])");
    $hasFallback       = str_contains($content, 'isDatabaseReachable');
    $hasSessionFall    = str_contains($content, 'SESSION_FALLBACK_DRIVER');

    return [
        'size'          => $size,
        'sha256_16'     => $sha256_16,
        'exists'        => true,
        'mtime_utc'     => gmdate('Y-m-d\TH:i:s\Z', $lastModified),
        'mtime_unix'    => $lastModified,
        'first_line'    => substr($firstLine, 0, 120),
        'signatures'    => array_filter([
            'standalone_probe_v1'       => $hasProbeV1,
            'configureProxyAndScheme'   => $hasConfigureProxy,
            'stateless_up_route'        => $hasStatelessUp,
            'custom_using_routing'      => $hasUsingRouting,
            'session_env_helper_fix'    => $hasEnvHelperFix,
            'pdo_connect_timeout'       => $hasPdoTimeout,
            'db_unavailable_fallback'   => $hasFallback,
            'session_fallback_driver'   => $hasSessionFall,
        ]),
    ];
}

$response = [
    'meta' => [
        'php'           => PHP_VERSION,
        'sapi'          => PHP_SAPI,
        'time_utc'      => gmdate('Y-m-d\TH:i:s\Z'),
        'hostname'      => gethostname(),
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null,
        'cwd'           => getcwd(),
        'uri'           => $_SERVER['REQUEST_URI'] ?? null,
        'https_server'  => ! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'server_port'   => $_SERVER['SERVER_PORT'] ?? null,
        'x_forwarded_proto' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
        'x_forwarded_ssl'   => $_SERVER['HTTP_X_FORWARDED_SSL'] ?? null,
        'x_forwarded_host'  => $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null,
        'x_forwarded_for'   => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
    ],
    'fingerprints' => [],
];

foreach ($buildFiles as $k => $path) {
    $response['fingerprints'][$k] = getFingerprint($path);
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
