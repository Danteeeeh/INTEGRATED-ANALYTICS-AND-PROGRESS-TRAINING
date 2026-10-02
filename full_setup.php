<?php

declare(strict_types=1);

/**
 * LMS Full Database Setup
 * -----------------------
 * One-command provisioning for the hosting environment:
 *   1. Reads DB credentials from env vars (DB_HOST/DB_USER/DB_PASS/DB_NAME/DB_PORT)
 *      with fallback to Laravel's .env/config, then to safe defaults.
 *   2. Creates the database if it does not exist yet.
 *   3. Generates APP_KEY if it is missing.
 *   4. Runs all migrations (php artisan migrate --force).
 *   5. Seeds all demo/system data (php artisan db:seed --force).
 *   6. Prints final row counts so you can verify everything landed.
 *
 * Run from the hosting console / terminal (project root):
 *   php full_setup.php
 *
 * CLI only — refuses to run through a web server for safety.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Read an env value, preferring the hosting-style names from the snippet:
 * DB_HOST / DB_USER / DB_PASS / DB_NAME / DB_PORT.
 */
function setup_env(string $key, string $fallback): string
{
    $value = getenv($key);

    return ($value !== false && $value !== '' && $value !== null) ? $value : $fallback;
}

$host = setup_env('DB_HOST', (string) Config::get('database.connections.mysql.host', 'localhost'));
$user = setup_env('DB_USER', setup_env('DB_USERNAME', (string) Config::get('database.connections.mysql.username', 'root')));
$pass = setup_env('DB_PASS', setup_env('DB_PASSWORD', (string) Config::get('database.connections.mysql.password', '')));
$db = setup_env('DB_NAME', setup_env('DB_DATABASE', (string) Config::get('database.connections.mysql.database', 'lms')));
$port = (int) setup_env('DB_PORT', (string) Config::get('database.connections.mysql.port', 3306));

echo "==============================================\n";
echo "  LMS Full Database Setup\n";
echo "==============================================\n";
echo "Host: {$host}:{$port} | Database: {$db} | User: {$user}\n\n";

// ── 1. Connect to the MySQL server (no database selected yet) ─────────────
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $user, $pass, '', $port);

if ($conn->connect_errno) {
    echo "[ERROR] Could not connect to MySQL server: {$conn->connect_error}\n";
    echo "Fix: set DB_HOST / DB_USER / DB_PASS / DB_PORT on the hosting\n";
    echo "      Environment step (or in .env), then run this again.\n";
    exit(1);
}

echo "[1/5] Connected to MySQL server. ({$conn->server_info})\n";

// ── 2. Create the database if it is missing ────────────────────────────────
$safeDb = $conn->real_escape_string($db);
$conn->query("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($conn->errno) {
    echo "[WARN] Could not auto-create database '{$db}': {$conn->error}\n";
    echo "       Create it manually on the hosting panel, e.g.:\n";
    echo "       CREATE DATABASE `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
} else {
    echo "[2/5] Database '{$db}' is ready (created if missing).\n";
}

$conn->close();

// ── 3. APP_KEY — generate only when missing ────────────────────────────────
$currentKey = (string) Config::get('app.key');

if ($currentKey === '' || $currentKey === 'base64:') {
    try {
        Artisan::call('key:generate', ['--force' => true]);
        echo '[3/5] APP_KEY was missing — generated fresh: '.trim(Artisan::output())."\n";
    } catch (Throwable $e) {
        echo "[WARN] Could not write APP_KEY automatically ({$e->getMessage()}).\n";
        echo "       Add APP_KEY manually on the hosting Environment step.\n";
    }
} else {
    echo "[3/5] APP_KEY is configured.\n";
}

// ── 4. Migrations ───────────────────────────────────────────────────────────
echo "[4/5] Running migrations...\n";
try {
    Artisan::call('migrate', ['--force' => true]);
    echo trim(Artisan::output())."\n";
    echo "[OK] Migrations applied.\n";
} catch (Throwable $e) {
    echo "[ERROR] Migration failed: {$e->getMessage()}\n";
    exit(1);
}

// ── 5. Seeders ──────────────────────────────────────────────────────────────
echo "[5/5] Seeding data...\n";
try {
    Artisan::call('db:seed', ['--force' => true]);
    echo trim(Artisan::output())."\n";
    echo "[OK] Seeders ran.\n";
} catch (Throwable $e) {
    echo "[ERROR] Seeding failed: {$e->getMessage()}\n";
    echo "Tip: the app may still work; re-run 'php full_setup.php' after fixing.\n";
    exit(1);
}

// ── Summary ─────────────────────────────────────────────────────────────────
echo "\n==============================================\n";
echo "  Row counts (verification)\n";
echo "==============================================\n";

foreach (['users', 'roles', 'academic_periods', 'courses', 'classes', 'enrollments', 'modules', 'lessons', 'question_banks', 'questions', 'quizzes', 'quiz_attempts', 'assignments', 'assignment_submissions', 'grade_items', 'grades', 'feedback', 'learning_plans', 'course_progress', 'module_progress', 'lesson_progress'] as $table) {
    try {
        echo str_pad($table, 28).': '.DB::table($table)->count()."\n";
    } catch (Throwable $e) {
        echo str_pad($table, 28).': ERR ('.$e->getMessage().")\n";
    }
}

echo "\nSetup finished. You can now deploy / restart the app.\n";
