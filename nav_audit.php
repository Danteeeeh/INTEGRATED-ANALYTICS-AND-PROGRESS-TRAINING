<?php
/**
 * Audits every Blade view against its sidebar: the $activeNav a view sets must
 * match a key the corresponding sidebar actually watches for, otherwise the nav
 * item never lights up.
 *
 *   php nav_audit.php
 */

$root = 'C:/xampp/htdocs/INTEGRATED-ANALYTICS-AND-PROGRESS-TRAINING/lms';

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
    $sidebarSrc = file_get_contents($root.$set['sidebar']);
    preg_match_all("/activeNav\s*===?\s*'([a-z_]+)'/", $sidebarSrc, $m);
    $valid = array_unique($m[1]);
    sort($valid);

    echo strtoupper($label).' — sidebar watches '.count($valid)." keys\n";

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root.$set['views'], FilesystemIterator::SKIP_DOTS)
    );

    $bad = [];

    foreach ($it as $file) {
        if ($file->isDir() || $file->getExtension() !== 'php') {
            continue;
        }

        $src = file_get_contents($file->getPathname());

        if (! preg_match("/activeNav\s*=\s*'([a-z_]+)'/", $src, $am)) {
            continue;
        }

        if (! in_array($am[1], $valid, true)) {
            $bad[] = [str_replace($root.'/', '', $file->getPathname()), $am[1]];
        }
    }

    if ($bad) {
        $failed = true;
        echo '  MISMATCHED ('.count($bad)."):\n";
        foreach ($bad as [$path, $value]) {
            echo "    - {$path} sets '{$value}'\n";
        }
    } else {
        echo "  OK — every view uses a valid sidebar key.\n";
    }

    echo "\n";
}

exit($failed ? 1 : 0);