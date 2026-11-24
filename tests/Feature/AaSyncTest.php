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

    public function test_sync_history_renders_all_eight_rows_newest_first_with_failed_status(): void
    {
        $admin = $this->actingAdmin();


        $statuses = [
            AaSyncLog::STATUS_SUCCESS,
            AaSyncLog::STATUS_SUCCESS,
            AaSyncLog::STATUS_SUCCESS,
            AaSyncLog::STATUS_FAILED,
            AaSyncLog::STATUS_SUCCESS,
            AaSyncLog::STATUS_SUCCESS,
            AaSyncLog::STATUS_SUCCESS,
            AaSyncLog::STATUS_SUCCESS,
        ];

        $logs = collect();
        foreach (range(1, 8) as $index) {
            $startedAt = now()->subMinutes(10 - $index)->startOfMinute();

            $logs->push(AaSyncLog::create([
                'performed_by' => $admin->id,
                'entities' => ['departments'],
                'status' => $statuses[$index - 1],
                'records_processed' => 0,
                'records_created' => 0,
                'records_updated' => 0,
                'records_skipped' => 0,
                'records_failed' => $statuses[$index - 1] === AaSyncLog::STATUS_FAILED ? 1 : 0,
                'started_at' => $startedAt,
                'finished_at' => $startedAt->copy()->addSeconds(5),
            ]));
        }

        $response = $this->actingAs($admin)->get('/admin/aa-sync');
        $response->assertOk();

        $rows = $this->historyRows($response->getContent());

        $this->assertCount(8, $rows, 'All eight sync history rows must render.');
        $this->assertSame(
            $logs->reverse()->map(fn (AaSyncLog $log) => [
                'when' => $log->started_at->format('M j, Y g:i A'),
                'status' => ucfirst($log->status),
            ])->values()->all(),
            $rows,
            'Sync history must render newest first without dropping any rows.'
        );
        $this->assertContains('Failed', array_column($rows, 'status'));
    }

    /**
     * Extract the Sync history table's row text for order/status assertions.
     */
    private function historyRows(string $html): array
    {
        $previousErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $xpath = new \DOMXPath($dom);

        $card = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " crud-card ")][.//h3[normalize-space() = "Sync history"]]')->item(0);

        if (! $card) {
            return [];
        }

        $rows = [];
        foreach ($xpath->query('.//tbody/tr', $card) as $row) {
            $cells = $xpath->query('./td', $row);
            $rows[] = [
                'when' => trim($cells->item(0)?->textContent ?? ''),
                'status' => trim($cells->item(2)?->textContent ?? ''),
            ];
        }

        return $rows;
    }
}
