<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Database backup / restore for the LMS.
 *
 * Uses `mysqldump` when available (XAMPP ships it), otherwise falls back to a
 * portable JSON snapshot of every table. Backups live in storage/app/backups.
 */
class BackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        File::ensureDirectoryExists($this->backupDir);
    }

    /**
     * List existing backup files, newest first.
     *
     * @return array<int, array{name: string, size: int, modified_at: string}>
     */
    public function list(): array
    {
        if (! is_dir($this->backupDir)) {
            return [];
        }

        $files = collect(File::files($this->backupDir))
            ->filter(fn ($file) => in_array($file->getExtension(), ['sql', 'json'], true))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->values()
            ->all();

        return $files;
    }

    /**
     * Create a backup. Prefers mysqldump; falls back to JSON.
     */
    public function create(): string
    {
        $stamp = now()->format('Y-m-d_His');
        $sql = $this->tryMysqldump();

        if ($sql !== null) {
            $name = "lms_backup_{$stamp}.sql";
            File::put($this->backupDir.'/'.$name, $sql);

            return $name;
        }

        $name = "lms_backup_{$stamp}.json";
        File::put($this->backupDir.'/'.$name, json_encode($this->snapshot(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $name;
    }

    /**
     * Attempt a real mysqldump. Returns SQL string or null when unavailable.
     */
    protected function tryMysqldump(): ?string
    {
        $candidates = [
            'C:\xampp\mysql\bin\mysqldump.exe',
            '/c/xampp/mysql/bin/mysqldump.exe',
            'mysqldump',
        ];

        $mysqldump = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate) || $candidate === 'mysqldump') {
                $mysqldump = $candidate;
                break;
            }
        }

        if (! $mysqldump) {
            return null;
        }

        $config = config('database.connections.'.config('database.default'));

        if (! $config || ($config['driver'] ?? null) !== 'mysql') {
            return null;
        }

        $cmd = sprintf(
            '"%s" --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s 2>/dev/null',
            $mysqldump,
            escapeshellarg($config['host'] ?? '127.0.0.1'),
            escapeshellarg((string) ($config['port'] ?? 3306)),
            escapeshellarg($config['username'] ?? 'root'),
            escapeshellarg((string) ($config['password'] ?? '')),
            escapeshellarg($config['database'] ?? '')
        );

        $output = shell_exec($cmd);

        return is_string($output) && str_contains($output, 'CREATE TABLE') ? $output : null;
    }

    /**
     * Portable JSON snapshot of every table.
     */
    public function snapshot(): array
    {
        $data = [];
        $tables = $this->tableNames();

        foreach ($tables as $tableName) {
            $data[$tableName] = DB::table($tableName)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $data;
    }

    /**
     * Portable table listing that works on both MySQL and SQLite.
     */
    protected function tableNames(): array
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')
                ->all();
        }

        return collect(Schema::getAllTables())
            ->map(fn ($row) => (array) $row)
            ->map(fn ($row) => reset($row))
            ->all();
    }

    /**
     * Restore a backup file (SQL or JSON).
     */
    public function restore(string $filename): void
    {
        $path = $this->resolvePath($filename);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension === 'sql') {
            $this->restoreSql($path);

            return;
        }

        if ($extension === 'json') {
            $this->restoreJson($path);

            return;
        }

        throw new RuntimeException('Unsupported backup format.');
    }

    protected function restoreSql(string $path): void
    {
        $sql = File::get($path);

        // Execute the dump statement by statement (portable across drivers).
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        DB::transaction(function () use ($statements) {
            foreach ($statements as $statement) {
                if (str_starts_with($statement, '--') || $statement === '') {
                    continue;
                }
                DB::statement($statement);
            }
        });
    }

    protected function restoreJson(string $path): void
    {
        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            throw new RuntimeException('Invalid JSON backup file.');
        }

        DB::transaction(function () use ($data) {
            foreach ($data as $table => $rows) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                DB::table($table)->truncate();
                foreach ($rows as $row) {
                    DB::table($table)->insert($row);
                }
            }
        });
    }

    /**
     * Resolve a backup filename to an absolute path, blocking traversal.
     */
    public function resolvePath(string $filename): string
    {
        $name = basename($filename);

        if (! Str::endsWith($name, ['.sql', '.json'])) {
            throw new RuntimeException('Invalid backup filename.');
        }

        $path = $this->backupDir.'/'.$name;

        if (! File::exists($path)) {
            throw new RuntimeException('Backup file not found.');
        }

        return $path;
    }

    public function directory(): string
    {
        return $this->backupDir;
    }
}
