<?php
/**
 * Prints the body of every admin controller's destroy() method so the delete
 * behaviour (soft vs hard, authorization, cascade) can be reviewed at a glance.
 *
 *   php delete_audit.php
 */

$root = __DIR__.'/app/Http/Controllers/Admin';

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

foreach ($it as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') {
        continue;
    }

    $lines = file($file->getPathname());
    $start = null;

    foreach ($lines as $i => $line) {
        if (preg_match('/function\s+destroy\s*\(/', $line)) {
            $start = $i;
            break;
        }
    }

    if ($start === null) {
        continue;
    }

    $body = [];
    $depth = 0;
    $opened = false;

    for ($i = $start; $i < count($lines); $i++) {
        $body[] = rtrim($lines[$i]);
        $depth += substr_count($lines[$i], '{') - substr_count($lines[$i], '}');

        if (str_contains($lines[$i], '{')) {
            $opened = true;
        }

        if ($opened && $depth === 0) {
            break;
        }
    }

    $text = implode("\n", $body);

    $flags = [];

    if (str_contains($text, '$this->authorize') || str_contains($text, 'authorizeResource')) {
        $flags[] = 'AUTHORIZED';
    } else {
        $flags[] = '!! NO-AUTHZ';
    }

    if (str_contains($text, 'forceDelete')) {
        $flags[] = 'HARD-DELETE';
    } elseif (preg_match('/(?<!force)->delete\(\)/', $text)) {
        $flags[] = 'delete()';
    }

    if (str_contains($text, 'authorizeResource')) {
        $flags[] = 'policy:class';
    }

    printf("%-28s %s\n", $file->getBasename('.php'), implode('  ', $flags));
}