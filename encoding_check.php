<?php
/**
 * Finds files that are not valid UTF-8.
 *
 * A single mangled byte in a Blade template produces a PHP parse error that
 * points at an unrelated line, so it is much easier to hunt down like this.
 *
 *   php encoding_check.php
 */

$root = __DIR__;

$skip = ['/vendor/', '/node_modules/', '/storage/', '/.git/', '/public/build/'];

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        function ($current) use ($skip) {
            $path = str_replace('\\', '/', (string) $current);

            foreach ($skip as $fragment) {
                if (str_contains($path, $fragment)) {
                    return false;
                }
            }

            return true;
        }
    )
);

$extensions = ['php', 'blade.php', 'js', 'css', 'json', 'env', 'md', 'txt'];
$offenders = [];

foreach ($iterator as $file) {
    if ($file->isDir()) {
        continue;
    }

    $name = $file->getFilename();

    $matches = in_array($name, $extensions, true) || str_ends_with($name, '.blade.php');

    if (! $matches) {
        continue;
    }

    $contents = (string) file_get_contents($file->getPathname());

    if ($contents === '' || mb_check_encoding($contents, 'UTF-8')) {
        continue;
    }

    // Report the first offending byte so the file can be pinpointed.
    $offset = 0;
    $length = strlen($contents);

    for ($i = 0; $i < $length;) {
        $char = mb_ord(mb_substr($contents, $i, 1, 'UTF-8'), 'UTF-8');

        if ($char === false) {
            $offset = $i;

            break;
        }

        $i += mb_strlen(mb_substr($contents, $i, 1, 'UTF-8'), 'UTF-8');
    }

    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;

    $offenders[] = [
        'file' => str_replace($root.'/', '', str_replace('\\', '/', $file->getPathname())),
        'line' => $line,
    ];
}

if ($offenders === []) {
    echo "OK — every PHP/Blade/JS/CSS file is valid UTF-8.\n";

    exit(0);
}

printf("%d file(s) contain invalid UTF-8:\n\n", count($offenders));

foreach ($offenders as $o) {
    printf("  %s (first bad byte near line %d)\n", $o['file'], $o['line']);
}

exit(1);