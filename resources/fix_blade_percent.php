<?php
/**
 * Rewrites `{{ $x }}%` into `{{ $x }}&percnt;` where the bare percent is
 * dangerous.
 *
 * Blade compiles `{{ $x }}%` to `?>%`. PHP's closing tag only implies a
 * semicolon when a newline follows it, so a `%` on the same line is lexed as the
 * modulo operator and the following word becomes its operand — turning the whole
 * template into a parse error. `&percnt;` renders identically and keeps the `%`
 * out of PHP's reach.
 *
 * Only the risky shapes are rewritten: the character right after the percent must
 * be a letter or a semicolon. A `}}%` followed by `<`, `"` or `</span>` is
 * harmless and is left alone so the markup stays untouched.
 *
 *   php fix_blade_percent.php          # apply
 *   php fix_blade_percent.php --dry    # report only
 */

$root = __DIR__;
$dryRun = in_array('--dry', $argv, true);

// The views directory is passed as a forward-slash path, so normalise before
// comparing against $root.'/' when building the report.
$viewsDir = $root.'/resources/views';

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsDir, FilesystemIterator::SKIP_DOTS)
);

$changed = [];

foreach ($iterator as $file) {
    if ($file->isDir() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $path = $file->getPathname();
    $original = (string) file_get_contents($path);

    // }}% followed by a letter or ; (optionally after whitespace) — the shapes
    // that break PHP parsing. A `}}%` followed by `<` is left untouched.
    $updated = preg_replace('/\}\}( ?)%(?=\s*[A-Za-z;])/', '}}$1&percnt;', $original);

    if ($updated === null || $updated === $original) {
        continue;
    }

    $relative = str_replace($root.'/', '', str_replace('\\', '/', $path));

    $changed[] = $relative;

    if (! $dryRun) {
        file_put_contents($path, $updated);
    }
}

if ($changed === []) {
    echo "Nothing to fix — no dangerous {{ ... }}% sequences remain.\n";

    exit(0);
}

printf("%s %d file(s):\n\n", $dryRun ? 'Would fix' : 'Fixed', count($changed));

foreach ($changed as $file) {
    echo "  $file\n";
}

if (! $dryRun) {
    echo "\nRun: php artisan view:clear && php artisan view:cache\n";
}