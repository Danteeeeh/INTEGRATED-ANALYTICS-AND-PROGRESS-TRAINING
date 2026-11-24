<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupRestoreUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_backup_with_embedded_php_is_rejected_before_it_is_stored(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $admin = User::factory()->admin()->create(['status' => 'active']);

        $file = UploadedFile::fake()
            ->createWithContent('restore.sql', "harmless header\n<?php echo 'safe'; ?>\n")
            ->mimeType('application/sql');

        $response = $this->actingAs($admin)->post(route('admin.backup.restore'), [
            'backup_file' => $file,
        ]);

        $response->assertSessionHasErrors([
            'backup_file' => 'The file contains suspicious content and cannot be uploaded.',
        ]);

        $this->assertSame([], File::glob(storage_path('app/backups/restore_*.sql')));
    }
}
