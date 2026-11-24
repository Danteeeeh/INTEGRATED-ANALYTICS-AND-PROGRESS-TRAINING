<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoragePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = storage_path('framework/testing/persistent-'.Str::uuid());

        File::ensureDirectoryExists($this->root);

        config([
            'filesystems.disks.local.root' => $this->root,
            'filesystems.disks.public.root' => $this->root.'/public',
            'filesystems.links' => [
                public_path('storage') => $this->root.'/public',
            ],
        ]);

        Storage::forgetDisk('local');
        Storage::forgetDisk('public');
    }

    protected function tearDown(): void
    {
        Storage::forgetDisk('local');
        Storage::forgetDisk('public');

        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_uploads_and_backups_land_in_the_configured_persistent_root(): void
    {
        Storage::disk('local')->put('uploads/probe.txt', 'upload payload');
        Storage::disk('public')->put('profile-photos/probe.txt', 'public payload');

        $this->assertFileExists($this->root.'/uploads/probe.txt');
        $this->assertFileExists($this->root.'/public/profile-photos/probe.txt');
        $this->assertSame('upload payload', File::get($this->root.'/uploads/probe.txt'));

        $backup = new BackupService;
        $name = $backup->createJson();

        $this->assertStringEndsWith('.json', $name);
        $this->assertFileExists($this->root.'/backups/'.$name);
        $this->assertSame($this->root.'/backups', $backup->directory());
    }

    public function test_a_backup_restores_from_the_persistent_root(): void
    {
        $user = User::factory()->student()->create(['email' => 'restore-me@example.test']);

        $backup = new BackupService;
        $name = $backup->createJson();

        $user->forceDelete();

        $this->assertDatabaseMissing('users', ['email' => 'restore-me@example.test']);

        (new BackupService)->restore($name);

        $this->assertDatabaseHas('users', [
            'email' => 'restore-me@example.test',
        ]);
    }

    public function test_storage_health_probes_every_root_and_marker_survives(): void
    {
        $this->artisan('lms:storage-health')
            ->assertExitCode(0);

        $this->artisan('lms:storage-health --write-marker')
            ->assertExitCode(0);

        $this->assertFileExists($this->root.'/.lms-storage-marker');

        $this->artisan('lms:storage-health --verify-marker')
            ->assertExitCode(0);

        File::delete($this->root.'/.lms-storage-marker');

        $this->artisan('lms:storage-health --verify-marker')
            ->assertExitCode(1);
    }

    public function test_release_preflight_rejects_storage_inside_the_release_directory(): void
    {
        $this->artisan('lms:storage-health --require-persistent')
            ->assertExitCode(1);
    }

    public function test_restore_rehearsal_runs_against_a_temporary_database(): void
    {
        User::factory()->student()->create(['email' => 'rehearsal@example.test']);

        $this->artisan('lms:backup-restore-test')
            ->assertExitCode(0);
    }
}
