<?php
/**
 * Lints every compiled Blade view and reports the source templates that do not
 * produce valid PHP.
 *
 * `php artisan view:cache` happily compiles broken templates — it never parses
 * the output — so a typo in a Blade directive shows up later as a 500 with a
 * confusing "unexpected end of file". This catches it up front.
 *
 *   php view_lint.php
 */

$root = __DIR__;
$viewDir = $root.'/storage/framework/views';

$compiled = glob($viewDir.'/*.php') ?: [];

$broken = [];

foreach ($compiled as $file) {
    // exec() appends to $output between calls, so it must be reset every time.
    $output = [];
    $code = 0;

    exec('php -l '.escapeshellarg($file).' 2>&1', $output, $code);

    $message = trim((string) ($output[0] ?? ''));

    // php -l exits 0 and prints "No syntax errors detected..." when the file is
    // valid, so trust the message rather than the exit code alone.
    if (str_contains($message, 'No syntax errors')) {
        continue;
    }

    $source = '';

    if (preg_match('#/\*\*PATH (.+?) ENDPATH\*\*/#', (string) file_get_contents($file), $m)) {
        $source = str_replace($root.'/', '', $m[1]);
    }

    $broken[] = [
        'compiled' => basename($file),
        'source' => $source ?: '(unknown template)',
        'error' => $message ?: 'could not be parsed',
    ];
}

$total = count($compiled);

if ($broken === []) {
    echo "OK — all {$total} compiled views are valid PHP.\n";

    exit(0);
}

printf("%d of %d compiled views do not parse:\n\n", count($broken), $total);

foreach ($broken as $issue) {
    echo '  '.$issue['source']."\n";
    echo '      '.$issue['error']."\n";
}

exit(1);