<?php

/**
 * Router for PHP's built-in web server, used by the responsive dashboard check
 * (tests/Browser).
 *
 * It mirrors what `php artisan serve` does — real files under `public/` are
 * streamed as-is, everything else is handed to the Laravel front controller —
 * so sessions, CSRF and Blade behave exactly as they do in a real deployment.
 *
 * The check launches `php -S` directly instead of `artisan serve` for two
 * reasons: it controls the child process's environment variables exactly, and
 * the server stays a single process the test runner can stop cleanly.
 */

$publicPath = __DIR__ . '/../../public';
$path = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

// Never resolve outside the document root.
if (str_contains($path, '..')) {
    http_response_code(404);

    return true;
}

if ($path !== '/' && is_file($publicPath . $path)) {
    return false; // let the built-in server stream the static file
}

require $publicPath . '/index.php';
