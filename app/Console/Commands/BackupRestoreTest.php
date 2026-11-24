<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Rehearse a restore without touching production data.
 *
 * The live database is only read. A portable JSON snapshot is replayed into a
 * freshly migrated SQLite database, then every table's row count is compared
 * with the source. This catches the failures that matter before a publish:
 * a corrupt backup, a migration that no longer matches the data, and a backup
 * that silently skips tables because the schema is incomplete.
 */
class BackupRestoreTest extends Command
{
    protected $signature = 'lms:backup-restore-test
        {--file= : Existing .json backup in the configured backup directory}';

    protected $description = 'Restore a backup into a temporary database and verify every table';

    public function handle(): int
    {
        $work = storage_path('framework/restore-tests/'.Str::uuid());
        $sqlite = $work.'/restore.sqlite';
        $connection = 'lms_restore_test';

        try {
            File::ensureDirectoryExists($work);
            File::put($sqlite, '');

            $sourceData = $this->loadSourceData($work);

            if ($sourceData === null) {
                return self::FAILURE;
            }

            $backup = $this->backupName;

            config([
                "database.connections.{$connection}" => [
                    'driver' => 'sqlite',
                    'database' => $sqlite,
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
            ]);

            DB::purge($connection);

            $exitCode = Artisan::call('migrate', [
                '--database' => $connection,
                '--force' => true,
                '--no-interaction' => true,
            ]);

            if ($exitCode !== 0) {
                $this->error('The temporary database could not be migrated.');
                $this->line(Artisan::output());

                return self::FAILURE;
            }

            $restore = new BackupService($connection, $work);
            $restore->restore($backup);

            $restored = $restore->snapshot();

            $missingTables = array_values(array_diff(
                array_keys($sourceData),
                array_keys($restored)
            ));

            if ($missingTables !== []) {
                $this->error('The restored schema is missing source tables: '.implode(', ', $missingTables));

                return self::FAILURE;
            }

            $mismatches = [];
            $tables = 0;
            $rows = 0;

            foreach ($sourceData as $table => $sourceRows) {
                $tables++;
                $sourceCount = count($sourceRows);
                $restoredCount = count($restored[$table] ?? []);

                $rows += $sourceCount;

                if ($sourceCount !== $restoredCount) {
                    $mismatches[] = "{$table}: {$sourceCount} -> {$restoredCount}";
                }
            }

            if ($mismatches !== []) {
                $this->error('Restore rehearsal found row-count mismatches:');
                foreach ($mismatches as $mismatch) {
                    $this->line('  - '.$mismatch);
                }

                return self::FAILURE;
            }

            $this->components->info("Restore rehearsal passed: {$tables} tables, {$rows} source rows.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->components->error('Restore rehearsal failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge($connection);
            File::deleteDirectory($work);
        }
    }

    /**
     * Read an existing JSON backup, or snapshot the live database into a
     * temporary JSON file. The live database is never written to.
     *
     * @return array<string, array<int, array<string, mixed>>>|null
     */
    private function loadSourceData(string $work): ?array
    {
        $file = $this->option('file');

        if (is_string($file) && $file !== '') {
            $source = app(BackupService::class)->resolvePath($file);

            if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'json') {
                $this->error('Only .json backups can be rehearsed in the temporary SQLite database.');

                return null;
            }

            $this->backupName = 'restore-test.json';
            File::copy($source, $work.'/'.$this->backupName);
        } else {
            $snapshot = app(BackupService::class)->snapshot();
            $encoded = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                $this->error('The live database snapshot could not be encoded as JSON.');

                return null;
            }

            $this->backupName = 'restore-test.json';
            File::put($work.'/'.$this->backupName, $encoded);
        }

        $data = json_decode(File::get($work.'/'.$this->backupName), true);

        if (! is_array($data)) {
            $this->error('The backup is not a valid JSON object.');

            return null;
        }

        return $data;
    }

    private string $backupName = 'restore-test.json';
}
