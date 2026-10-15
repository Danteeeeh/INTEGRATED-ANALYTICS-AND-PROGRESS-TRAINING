<?php

namespace Tests\Feature;

use App\Models\AaSyncLog;
use App\Models\User;
use App\Services\AaSyncService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Sync from AA" service is the only enrollment source in the Canvas-style
 * model. These tests pin the failure contract (an unreachable AA database is
 * logged, never silently ignored) and the entity filtering.
 */
class AaSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        // Point the AA connection at a database that cannot exist so every
        // connection attempt fails immediately instead of waiting on a
        // timeout. The sync must degrade into a failed log, not a crash.
        config(['database.connections.aa.database' => 'aa_database_that_does_not_exist']);
    }

    protected function actingAdmin(): User
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_connection_is_reported_unavailable_when_aa_is_unreachable(): void
    {
        $this->assertFalse(app(AaSyncService::class)->available());
    }

    public function test_run_records_a_failed_log_when_aa_is_unreachable(): void
    {
        $admin = $this->actingAdmin();

        $service = app(AaSyncService::class);
        $log = $service->run(['departments']);

        $this->assertSame(AaSyncLog::STATUS_FAILED, $log->status);
        $this->assertSame($admin->id, $log->performed_by);
        $this->assertSame(['departments'], $log->entities);
        $this->assertNotNull($log->error_message);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->finished_at);
        $this->assertSame(0, $log->records_created);
        $this->assertSame(0, $log->records_updated);

        $this->assertDatabaseHas('aa_sync_logs', [
            'id' => $log->id,
            'status' => AaSyncLog::STATUS_FAILED,
            'performed_by' => $admin->id,
        ]);
    }

    public function test_unknown_entities_are_dropped_from_the_run(): void
    {
        $this->actingAdmin();

        $log = app(AaSyncService::class)->run(['departments', 'definitely_not_an_entity']);

        $this->assertSame(['departments'], $log->entities);
    }

    public function test_run_defaults_to_the_configured_sync_order(): void
    {
        $this->actingAdmin();

        $log = app(AaSyncService::class)->run();

        $this->assertSame(config('aa_sync.sync_order'), $log->entities);
    }
}
