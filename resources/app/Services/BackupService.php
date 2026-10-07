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
        $sql = $this->tryMysqldump();
        $extension = $sql !== null ? 'sql' : 'json';

        // The timestamp alone collides when two backups land in the same second
        // (e.g. a manual backup right after a restore's safety snapshot), which
        // silently overwrote the earlier file.
        $name = $this->uniqueName($extension);

        $contents = $sql !== null
            ? $sql
            : json_encode($this->snapshot(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        File::put($this->backupDir.'/'.$name, $contents);

        return $name;
    }

    /**
     * Build a backup filename that cannot collide with an existing one.
     */
    protected function uniqueName(string $extension): string
    {
        $stamp = now()->format('Y-m-d_His');

        $name = "lms_backup_{$stamp}.{$extension}";

        if (! File::exists($this->backupDir.'/'.$name)) {
            return $name;
        }

        // Append a counter until the name is free.
        for ($suffix = 1; $suffix < 100; $suffix++) {
            $candidate = "lms_backup_{$stamp}_{$suffix}.{$extension}";

            if (! File::exists($this->backupDir.'/'.$candidate)) {
                return $candidate;
            }
        }

        return "lms_backup_{$stamp}_".substr(md5((string) microtime(true)), 0, 6).".{$extension}";
    }

    /**
     * Attempt a real mysqldump. Returns SQL string or null when unavailable.
     */
    protected function tryMysqldump(): ?string
    {
        $config = config('database.connections.'.config('database.default'));

        if (! $config || ! in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            return null;
        }

        $mysqldump = $this->locateMysqldump();

        if (! $mysqldump) {
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
     * Find a mysqldump binary across Windows/XAMPP and Linux paths.
     */
    protected function locateMysqldump(): ?string
    {
        $candidates = [
            'C:\xampp\mysql\bin\mysqldump.exe',
            '/c/xampp/mysql/bin/mysqldump.exe',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/opt/mariadb/bin/mysqldump',
            'mysqldump',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        // Last resort: ask the shell. Covers Homebrew and distro paths that are
        // not worth hard-coding.
        $which = @shell_exec('command -v mysqldump 2>/dev/null');

        return is_string($which) && trim($which) !== '' ? trim($which) : null;
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
     * Portable table listing across MySQL, MariaDB, SQLite, PostgreSQL and
     * SQL Server.
     *
     * Schema::getAllTables() was used here, but that only exists on
     * MySqlBuilder — calling it on a MariaDB connection throws
     * "Method MariaDbBuilder::getAllTables does not exist" and takes down the
     * whole /admin/backup page. SHOW FULL TABLES works on both engines.
     */
    protected function tableNames(): array
    {
        $driver = DB::connection()->getDriverName();

        $names = match ($driver) {
            'sqlite' => collect(DB::select(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"
            ))->pluck('name'),

            'mysql', 'mariadb' => collect(DB::select('SHOW FULL TABLES'))
                // SHOW FULL TABLES returns two columns: Tables_in_<db> and
                // Table_type. Keep base tables only, and skip the views.
                ->filter(fn ($row) => ((array) $row)['Table_type'] ?? 'BASE TABLE' === 'BASE TABLE')
                ->map(fn ($row) => array_values((array) $row)[0]),

            'pgsql' => collect(DB::select(
                "SELECT table_name AS name FROM information_schema.tables
                 WHERE table_schema = current_schema() AND table_type = 'BASE TABLE'"
            ))->pluck('name'),

            'sqlsrv' => collect(DB::select(
                "SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'"
            ))->pluck('name'),

            default => collect(),
        };

        return $names
            ->filter()
            ->map(fn ($name) => (string) $name)
            // Schema-qualified names would break DB::table() lookups.
            ->map(fn ($name) => Str::afterLast($name, '.'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Restore a backup file (SQL or JSON).
     *
     * Always takes a safety snapshot first — a restore replaces live data and
     * there is no undo once the tables are truncated.
     */
    public function restore(string $filename): void
    {
        $path = $this->resolvePath($filename);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $this->create();

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

        foreach ($this->splitSqlStatements($sql) as $statement) {
            DB::statement($statement);
        }
    }

    /**
     * Split a SQL dump into individual statements.
     *
     * A plain explode(';') is wrong for real mysqldump output: a semicolon
     * inside a string literal or a comment would cut a statement in half, and
     * the triggers/routines that --routines --triggers produce are wrapped in
     * DELIMITER blocks that only the mysql CLI understands. This parser is
     * quote-aware and simply keeps whole trigger bodies as one statement.
     *
     * @return array<int, string>
     */
    protected function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        // mysqldump wraps triggers and routines in "DELIMITER ;;" blocks. The
        // delimiter is a mysql CLI directive, not SQL, so it is tracked here and
        // the statement terminator switches with it — otherwise the semicolons
        // inside a BEGIN...END body would split it into broken fragments.
        $delimiter = ';';
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                    $current .= $char;
                }

                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }

                continue;
            }

            if (! $inSingle && ! $inDouble && ! $inBacktick) {
                // -- line comment
                if (($char === '-' && $next === '-') || $char === '#') {
                    $inLineComment = true;
                    $i += ($char === '-' && $next === '-') ? 1 : 0;

                    continue;
                }

                // /* block comment */
                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i++;

                    continue;
                }

                // DELIMITER <token>
                if (preg_match('/^DELIMITER[ \t]+(\S+)/i', substr($sql, $i), $m)) {
                    $delimiter = $m[1];
                    $i += strlen($m[0]) - 1;

                    continue;
                }
            }

            if ($char === "'" && ! $inDouble && ! $inBacktick) {
                // '' inside a single-quoted string is an escaped quote.
                if ($inSingle && $next === "'") {
                    $current .= $char.$next;
                    $i++;

                    continue;
                }

                $inSingle = ! $inSingle;
            } elseif ($char === '"' && ! $inSingle && ! $inBacktick) {
                if ($inDouble && $next === '"') {
                    $current .= $char.$next;
                    $i++;

                    continue;
                }

                $inDouble = ! $inDouble;
            } elseif ($char === '`' && ! $inSingle && ! $inDouble) {
                $inBacktick = ! $inBacktick;
            }

            if (! $inSingle && ! $inDouble && ! $inBacktick
                && str_starts_with(substr($sql, $i), $delimiter)) {
                $trimmed = trim($current);

                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }

                $current = '';
                $i += strlen($delimiter) - 1;

                continue;
            }

            $current .= $char;
        }

        $trailing = trim($current);

        if ($trailing !== '') {
            $statements[] = $trailing;
        }

        return $statements;
    }

    protected function restoreJson(string $path): void
    {
        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            throw new RuntimeException('Invalid JSON backup file.');
        }

        // Truncating with foreign keys live fails as soon as a child row still
        // points at a parent, and re-inserting in file order breaks for the
        // same reason. Suspend the checks for the duration of the restore.
        $this->withoutForeignKeyChecks(function () use ($data) {
            foreach ($data as $table => $rows) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->truncate();

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        });
    }

    /**
     * Run a callback with foreign key enforcement suspended.
     *
     * Two SQLite caveats drive this implementation:
     *   - "PRAGMA foreign_keys" is ignored while a transaction is open, so it
     *     is only issued when no transaction is active.
     *   - "PRAGMA defer_foreign_keys" does apply inside a transaction and
     *     pushes enforcement out to COMMIT, which keeps the restore correct
     *     even when a caller (or the test harness) already opened one.
     *
     * MySQL/MariaDB use the session-level FOREIGN_KEY_CHECKS switch, which is
     * honoured regardless of transaction state.
     */
    protected function withoutForeignKeyChecks(callable $callback): void
    {
        $driver = DB::connection()->getDriverName();
        $inTransaction = DB::transactionLevel() > 0;

        if ($driver === 'sqlite') {
            if (! $inTransaction) {
                DB::statement('PRAGMA foreign_keys = OFF');
            }

            DB::statement('PRAGMA defer_foreign_keys = ON');

            try {
                DB::transaction($callback);
            } finally {
                DB::statement('PRAGMA defer_foreign_keys = OFF');

                if (! $inTransaction) {
                    DB::statement('PRAGMA foreign_keys = ON');
                }
            }

            return;
        }

        $statement = $driver === 'pgsql'
            ? 'SET session_replication_role = replica'
            : 'SET FOREIGN_KEY_CHECKS = 0';

        $restore = $driver === 'pgsql'
            ? 'SET session_replication_role = origin'
            : 'SET FOREIGN_KEY_CHECKS = 1';

        DB::statement($statement);

        try {
            DB::transaction($callback);
        } finally {
            DB::statement($restore);
        }
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
