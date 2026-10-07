<?php
/**
 * Audits every Blade view against its sidebar.
 *
 * Works on both:
 * - Local Windows/XAMPP
 * - HostForge/Linux
 *
 * Run:
 *   php nav_audit.php
 */

$root = __DIR__;

$sets = [
    'admin' => [
        'sidebar' => '/resources/views/components/admin-sidebar.blade.php',
        'views' => '/resources/views/admin',
    ],
    'instructor' => [
        'sidebar' => '/resources/views/components/instructor-sidebar.blade.php',
        'views' => '/resources/views/instructor',
    ],
    'student' => [
        'sidebar' => '/resources/views/components/student-sidebar.blade.php',
        'views' => '/resources/views/student',
    ],
];

$failed = false;

foreach ($sets as $label => $set) {
    $sidebarPath = $root . $set['sidebar'];
    $viewsPath = $root . $set['views'];

    if (!is_file($sidebarPath)) {
        echo strtoupper($label) . " — ERROR: sidebar not found:\n";
        echo "  {$sidebarPath}\n\n";
        $failed = true;
        continue;
    }

    if (!is_dir($viewsPath)) {
        echo strtoupper($label) . " — ERROR: views directory not found:\n";
        echo "  {$viewsPath}\n\n";
        $failed = true;
        continue;
    }

    $sidebarSrc = file_get_contents($sidebarPath);

    preg_match_all(
        "/activeNav\s*===?\s*'([a-z_]+)'/",
        $sidebarSrc,
        $m
    );

    $valid = array_unique($m[1]);
    sort($valid);

    echo strtoupper($label) .
        ' — sidebar watches ' .
        count($valid) .
        " keys\n";

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $viewsPath,
            FilesystemIterator::SKIP_DOTS
        )
    );

    $bad = [];

    foreach ($it as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }

        $src = file_get_contents($file->getPathname());

        if (!preg_match(
            "/activeNav\s*=\s*'([a-z_]+)'/",
            $src,
            $am
        )) {
            continue;
        }

        if (!in_array($am[1], $valid, true)) {
            $relativePath = str_replace(
                $root . '/',
                '',
                str_replace('\\', '/', $file->getPathname())
            );

            $bad[] = [$relativePath, $am[1]];
        }
    }

    if ($bad) {
        $failed = true;

        echo '  MISMATCHED (' . count($bad) . "):\n";

        foreach ($bad as [$path, $value]) {
            echo "    - {$path} sets '{$value}'\n";
        }
    } else {
        echo "  OK — every view uses a valid sidebar key.\n";
    }

    echo "\n";
}

exit($failed ? 1 : 0);
