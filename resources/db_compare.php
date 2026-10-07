<?php
/**
 * Compares the tables that exist in your connected database against the tables
 * the migrations declare. Run it from the project root:
 *
 *   php db_compare.php
 *
 * Exit code 0 = every migration table exists.
 * Exit code 1 = one or more are missing (listed below).
 */

// Boot Laravel so we can use the DB facade with your real .env credentials.
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$migrationTables = [];
foreach (glob(__DIR__.'/database/migrations/*.php') as $file) {
    $src = file_get_contents($file);
    if (preg_match_all("/Schema::create\(\s*'([^']+)'/", $src, $m)) {
        foreach ($m[1] as $t) {
            // Migrations named remove_* intentionally DROP tables — skip those.
            if (! str_contains(basename($file), 'remove_')) {
                $migrationTables[$t] = basename($file);
            }
        }
    }
}
ksort($migrationTables);

try {
    $existing = Schema::getTableListing();
} catch (\Throwable $e) {
    fwrite(STDERR, "Could not connect to the database:\n  ".$e->getMessage()."\n");
    exit(2);
}
$existing = array_flip($existing);

$missing = array_diff_key($migrationTables, $existing);
$extra   = array_diff(array_keys($existing), array_keys($migrationTables));

echo "DB connected to: ".config('database.connections.'.config('database.default').'.database')."\n";
echo 'Migrations declare : '.count($migrationTables)." tables\n";
echo 'Present in DB      : '.count($existing)." tables\n\n";

if ($missing) {
    echo "MISSING (".count($missing).") — the app will error on these:\n";
    foreach ($missing as $t => $f) {
        echo "  - {$t}".str_repeat(' ', max(1, 34 - strlen($t)))."<- {$f}\n";
    }
} else {
    echo "MISSING            : none — every migration table exists.\n";
}

if ($extra) {
    echo "\nEXTRA (".count($extra).") — in the DB but not created by migrations (harmless, often seeders/tools):\n";
    foreach ($extra as $t) {
        if (in_array($t, ['migrations', 'cache', 'cache_locks', 'sessions', 'jobs', 'failed_jobs'], true)) {
            continue;
        }
        echo "  + {$t}\n";
    }
}

echo "\nDone.\n";
exit($missing ? 1 : 0);