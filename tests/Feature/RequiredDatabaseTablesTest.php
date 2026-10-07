<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * A configured database driver fails on every request when the table it names
 * does not exist.
 *
 * `CACHE_STORE=database` with no `cache` table took the whole site down with
 * `SQLSTATE[42S02] ... Table 'hf_db_htrkhzcm.cache' doesn't exist`, and it did so
 * silently: the table's migration was committed, so `php artisan migrate` had
 * already been run once against it and will never try again.
 *
 * This asserts the weaker, structural thing that is cheap to keep true: for
 * every table this application's configuration can demand, some migration
 * creates it.
 */
class RequiredDatabaseTablesTest extends TestCase
{
    /**
     * Driver => tables it needs, for the drivers this app actually configures.
     *
     * @return array<string, array<int, string>>
     */
    private function requiredTables(): array
    {
        return [
            'cache' => ['cache', 'cache_locks'],
            'session' => ['sessions'],
        ];
    }

    public function test_every_configured_database_driver_has_a_table_some_migration_creates(): void
    {
        $created = $this->tablesCreatedByMigrations();

        foreach ($this->requiredTables() as $driver => $tables) {
            foreach ($tables as $table) {
                $this->assertArrayHasKey(
                    $table,
                    $created,
                    sprintf(
                        'The "%s" driver needs a `%s` table, but no migration creates it. '.
                        'Either add the migration or point the driver at a file-backed store.',
                        $driver,
                        $table
                    )
                );
            }
        }
    }

    public function test_the_self_healing_migration_covers_the_cache_tables(): void
    {
        // The original cache migrations only run once. If a table goes missing
        // afterwards — a partial database import, a hand-dropped table — the
        // recovery path has to rebuild it, because `migrate` will report that
        // there is nothing to do.
        $source = file_get_contents(
            dirname(__DIR__, 2).'/database/migrations/2026_10_07_000000_recreate_missing_tables.php'
        );

        $this->assertIsString($source);

        foreach (['cache', 'cache_locks'] as $table) {
            $this->assertStringContainsString(
                "hasTable('{$table}')",
                $source,
                "The self-healing migration must be able to rebuild `{$table}`."
            );

            $this->assertStringContainsString(
                "Schema::create('{$table}'",
                $source,
                "The self-healing migration must be able to rebuild `{$table}`."
            );
        }

        // Guards alone are not enough: the recovery has to actually be invoked
        // from up(), or it is dead code that looks like a fix.
        $this->assertStringContainsString(
            'createCacheTables()',
            $this->upMethodBody($source),
            'up() must call the cache-table recovery, or the guards never run.'
        );
    }

    private function upMethodBody(string $source): string
    {
        $matched = preg_match('/public function up\(\): void\s*\{(.*?)\n    \}/s', $source, $m);

        $this->assertSame(1, $matched, 'Could not locate up() in the self-healing migration.');

        return $m[1];
    }

    /**
     * Every table named in a Schema::create() across all migrations.
     *
     * @return array<string, true>
     */
    private function tablesCreatedByMigrations(): array
    {
        $directory = dirname(__DIR__, 2).'/database/migrations';

        $tables = [];

        foreach (glob($directory.'/*.php') ?: [] as $file) {
            $source = file_get_contents($file);

            if ($source === false) {
                continue;
            }

            if (preg_match_all(
                "/Schema::create\(\s*'([a-z_]+)'/i",
                $source,
                $matches
            )) {
                foreach ($matches[1] as $table) {
                    $tables[$table] = true;
                }
            }
        }

        return $tables;
    }
}
