<?php

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

$writeDir = __DIR__.'/../storage/framework/views';
if (is_dir($writeDir) && is_writable($writeDir)) {
    http_response_code(200);
    echo "ok\n";
} else {
    http_response_code(503);
    echo "degraded: storage/framework/views missing or not writable\n";
}
exit;
