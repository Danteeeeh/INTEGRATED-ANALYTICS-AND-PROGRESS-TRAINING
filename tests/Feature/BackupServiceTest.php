<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BackupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->admin()->create(['status' => 'active']);

        $this->service = new BackupService;

        $this->service->directory();
    }

    protected function tearDown(): void
    {
        $dir = storage_path('app/backups');

        if (is_dir($dir)) {
            File::cleanDirectory($dir);
        }

        parent::tearDown();
    }

    /**
     * Reach a protected method on the service. Named invoke() because Laravel's
     * TestCase already defines a public call().
     */
    protected function invoke(string $method, mixed ...$args): mixed
    {
        return (new \ReflectionMethod($this->service, $method))->invoke($this->service, ...$args);
    }

    public function test_table_listing_works_on_sqlite(): void
    {
        $tables = $this->invoke('tableNames');

        $this->assertNotEmpty($tables, 'Table listing returned nothing.');
        $this->assertContains('users', $tables);
        $this->assertContains('enrollments', $tables);
    }

    public function test_table_listing_never_uses_schema_get_all_tables(): void
    {
        // Schema::getAllTables() only exists on MySqlBuilder; on MariaDB it
        // throws BadMethodCallException and 500s /admin/backup. Guard the
        // regression at the source level, ignoring comments and doc blocks.
        $source = file_get_contents(app_path('Services/BackupService.php'));

        $code = preg_replace(['#/\*.*?\*/#s', '#//[^\n]*#'], '', $source) ?? '';

        $this->assertStringNotContainsString(
            'Schema::getAllTables()',
            $code,
            'Schema::getAllTables() is MySQL-only and breaks MariaDB.'
        );
    }

    public function test_snapshot_captures_every_table(): void
    {
        $snapshot = $this->service->snapshot();

        $this->assertArrayHasKey('users', $snapshot);
        $this->assertArrayHasKey('roles', $snapshot);
        $this->assertCount(1, $snapshot['users']);
    }

    public function test_backup_page_renders(): void
    {
        $this->actingAs($this->admin)->get(route('admin.backup.index'))->assertOk();
    }

    public function test_creating_a_backup_produces_a_downloadable_file(): void
    {
        $name = $this->service->create();

        $this->assertFileExists($this->service->directory().'/'.$name);

        $listed = collect($this->service->list())->pluck('name');
        $this->assertTrue($listed->contains($name));

        // Whatever the format, it must not be empty.
        $this->assertGreaterThan(0, File::size($this->service->directory().'/'.$name));
    }

    public function test_backup_page_survives_the_create_action(): void
    {
        // The reported failure: opening /admin/backup threw
        // "Method MariaDbBuilder::getAllTables does not exist".
        $this->actingAs($this->admin)
            ->post(route('admin.backup.create'))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('admin.backup.index'))
            ->assertOk();
    }

    public function test_path_traversal_is_blocked(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->resolvePath('../../.env');
    }

    public function test_unknown_backup_file_is_rejected(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->resolvePath('does_not_exist.sql');
    }

    public function test_sql_splitter_keeps_semicolons_inside_strings(): void
    {
        $sql = <<<'SQL'
INSERT INTO announcements (title, body) VALUES ('Semicolons; inside', 'Another; one; here');
INSERT INTO courses (title) VALUES ('Plain');
SQL;

        $statements = $this->invoke('splitSqlStatements', $sql);

        $this->assertCount(2, $statements);
        $this->assertStringContainsString('Another; one; here', $statements[0]);
        $this->assertStringContainsString("VALUES ('Plain')", $statements[1]);
    }

    public function test_sql_splitter_handles_escaped_quotes(): void
    {
        $sql = "INSERT INTO t (a) VALUES ('it''s fine; really'); INSERT INTO t (b) VALUES (2);";

        $statements = $this->invoke('splitSqlStatements', $sql);

        $this->assertCount(2, $statements);
        $this->assertStringContainsString("it''s fine; really", $statements[0]);
    }

    public function test_sql_splitter_keeps_trigger_bodies_whole(): void
    {
        // mysqldump --triggers emits this. A naive explode(';') would chop the
        // body into two broken statements.
        $sql = <<<'SQL'
DELIMITER ;;
CREATE TRIGGER trg_users AFTER INSERT ON users FOR EACH ROW
BEGIN
    INSERT INTO audit_log (action) VALUES ('created');
    INSERT INTO audit_log (action) VALUES ('second');
END ;;
DELIMITER ;
SQL;

        $statements = $this->invoke('splitSqlStatements', $sql);

        $this->assertCount(1, $statements, 'Trigger body must stay a single statement.');
        $this->assertStringNotContainsString('DELIMITER', $statements[0]);
        $this->assertStringContainsString('END', $statements[0]);
        $this->assertStringContainsString("'second'", $statements[0]);
    }

    public function test_sql_splitter_strips_comments(): void
    {
        $sql = <<<'SQL'
-- a leading comment; with a semicolon
# another comment; here
/* block; comment */
INSERT INTO t (a) VALUES (1);
SQL;

        $statements = $this->invoke('splitSqlStatements', $sql);

        $this->assertCount(1, $statements);
        $this->assertSame('INSERT INTO t (a) VALUES (1)', $statements[0]);
    }

    public function test_restore_round_trips_a_json_snapshot(): void
    {
        $name = $this->service->create();

        $this->assertStringEndsWith('.json', $name);

        // Wipe, then restore.
        \DB::table('users')->truncate();

        $this->service->restore($name);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', ['email' => $this->admin->email]);
    }

    public function test_restore_takes_a_safety_backup_first(): void
    {
        $name = $this->service->create();
        $before = count($this->service->list());

        $this->service->restore($name);

        $this->assertGreaterThan(
            $before,
            count($this->service->list()),
            'A restore must leave a recoverable snapshot behind.'
        );
    }

    public function test_mysqldump_is_not_attempted_for_sqlite(): void
    {
        $this->assertNull(
            $this->invoke('tryMysqldump'),
            'SQLite must fall back to the JSON snapshot, not shell out to mysqldump.'
        );
    }
}