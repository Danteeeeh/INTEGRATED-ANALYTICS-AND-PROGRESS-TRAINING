<?php

namespace Tests\Feature\Authorization;

use App\Models\Role;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSystemFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::ADMIN)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    protected function makeInstructor(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    protected function makeStudent(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_permissions_index(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.permissions.index'))
            ->assertStatus(200)
            ->assertSee('Manage Permissions');
    }

    public function test_admin_can_edit_instructor_role_permissions(): void
    {
        $instructorRole = Role::where('slug', Role::INSTRUCTOR)->firstOrFail();

        $this->actingAs($this->makeAdmin())
            ->get(route('admin.permissions.edit', $instructorRole))
            ->assertStatus(200)
            ->assertSee('courses.view');

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.permissions.update', $instructorRole), [
                'permissions' => [
                    \App\Models\Permission::where('name', 'courses.view')->firstOrFail()->id,
                    \App\Models\Permission::where('name', 'classes.view')->firstOrFail()->id,
                ],
            ])
            ->assertRedirect(route('admin.permissions.index'));

        $this->assertSame(
            ['classes.view', 'courses.view'],
            $instructorRole->fresh()->permissions->pluck('name')->sort()->values()->all()
        );
    }

    public function test_admin_role_permissions_are_fixed_and_cannot_be_edited(): void
    {
        $adminRole = Role::where('slug', Role::ADMIN)->firstOrFail();
        $before = $adminRole->permissions->count();

        $this->actingAs($this->makeAdmin())
            ->put(route('admin.permissions.update', $adminRole), [
                'permissions' => [],
            ])
            ->assertSessionHas('error');

        $this->assertSame($before, $adminRole->fresh()->permissions->count());
    }

    public function test_admin_can_view_backup_index_and_create_backup(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.backup.index'))
            ->assertStatus(200)
            ->assertSee('Backup & Restore');

        $this->actingAs($admin)
            ->post(route('admin.backup.create'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $backups = app(BackupService::class)->list();
        $this->assertNotEmpty($backups);
    }

    public function test_instructor_cannot_access_admin_permissions_or_backup(): void
    {
        $instructor = $this->makeInstructor();

        $this->actingAs($instructor)
            ->get(route('admin.permissions.index'))
            ->assertStatus(403);

        $this->actingAs($instructor)
            ->get(route('admin.backup.index'))
            ->assertStatus(403);
    }

    public function test_student_cannot_access_admin_permissions_or_backup(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($student)
            ->get(route('admin.permissions.index'))
            ->assertStatus(403);

        $this->actingAs($student)
            ->get(route('admin.backup.index'))
            ->assertStatus(403);
    }

    public function test_guest_cannot_access_admin_permissions_or_backup(): void
    {
        $this->get(route('admin.permissions.index'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.backup.index'))
            ->assertRedirect(route('login'));
    }
}
