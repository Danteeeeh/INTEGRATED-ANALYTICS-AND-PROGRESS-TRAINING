<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$status = [
    'status' => 'ok',
    'probe'  => 'healthz-standalone',
    'php'    => PHP_VERSION,
    'sapi'   => PHP_SAPI,
    'time'   => date('c'),
    'writable' => null,
];

$writeDir = __DIR__.'/../storage/framework/views';
if (is_dir($writeDir) && is_writable($writeDir)) {
    $status['writable'] = true;
    $testFile = $writeDir.'/.healthz_writable_check_'.getmypid().'.tmp';
    if (@file_put_contents($testFile, 'ok') !== false) {
        @unlink($testFile);
    } else {
        $status['writable'] = false;
        $status['warning']  = 'storage/framework/views is NOT writable by '.get_current_user();
        http_response_code(503);
    }
} else {
    $status['writable'] = false;
    $status['warning']  = 'storage/framework/views directory missing or not writable';
    http_response_code(503);
}

echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
exit;
