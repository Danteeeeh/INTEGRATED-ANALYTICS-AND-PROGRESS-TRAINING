<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HideRegistrarUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('slug', Role::ADMIN)->firstOrFail()->id,
            'status' => 'active',
        ]);

        // The leftover demo account that was still showing up in /admin/users.
        $this->registrar = User::factory()->create([
            'role_id' => Role::where('slug', Role::REGISTRAR)->firstOrFail()->id,
            'status' => 'active',
            'email' => 'registrar@lms.local',
            'identifier' => 'REG-0001',
            'first_name' => 'Demo',
            'last_name' => 'Registrar',
        ]);
    }

    protected User $registrar;

    public function test_admin_users_page_hides_the_registrar(): void
    {
        // This is the exact regression: the Users page reads UserService::
        // getAllUsers(), which previously had no role filter at all.
        $emails = $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->viewData('users')
            ->getCollection()
            ->pluck('email');

        $this->assertFalse(
            $emails->contains('registrar@lms.local'),
            'Registrar must not appear in the admin Users list.'
        );
    }

    public function test_get_all_users_excludes_the_registrar(): void
    {
        $paginator = app(UserService::class)->getAllUsers();

        $this->assertFalse(
            $paginator->getCollection()->contains('id', $this->registrar->id),
            'UserService::getAllUsers() must exclude the retired registrar role.'
        );

        $this->assertTrue($paginator->getCollection()->contains('id', $this->admin->id));
    }

    public function test_registrar_stays_hidden_when_filtering_and_searching(): void
    {
        $service = app(UserService::class);

        $searched = $service->getAllUsers(['search' => 'Registrar']);
        $this->assertSame(0, $searched->total(), 'Searching for the registrar must return nothing.');

        // Filtering by registrar should be empty too, not a leaky single row.
        $filtered = $service->getAllUsers(['role_slug' => Role::REGISTRAR]);
        $this->assertSame(0, $filtered->total());

        // A general search must not surface it either.
        $general = $service->getAllUsers(['search' => 'Demo']);
        $this->assertFalse($general->getCollection()->contains('id', $this->registrar->id));
    }

    public function test_registrar_is_not_offered_as_a_filter_option(): void
    {
        $roles = $this->actingAs($this->admin)
            ->get('/admin/users')
            ->assertOk()
            ->viewData('roles');

        $this->assertFalse(
            $roles->contains('slug', Role::REGISTRAR),
            'Registrar must not appear in the role filter dropdown.'
        );
        $this->assertGreaterThan(0, $roles->count(), 'Other roles must remain listed.');
    }

    public function test_registrar_cannot_be_assigned_from_any_user_form(): void
    {
        foreach (['/admin/users/create', '/admin/students/create', '/admin/instructors/create'] as $url) {
            $roles = $this->actingAs($this->admin)->get($url)->assertOk()->viewData('roles');

            $this->assertFalse(
                $roles->contains('slug', Role::REGISTRAR),
                "Registrar is still assignable from {$url}."
            );
        }
    }

    public function test_assignable_scope_keeps_every_other_role(): void
    {
        $slugs = Role::assignable()->orderBy('name')->pluck('slug');

        $this->assertFalse($slugs->contains(Role::REGISTRAR));
        $this->assertTrue($slugs->contains(Role::ADMIN));
        $this->assertTrue($slugs->contains(Role::INSTRUCTOR));
        $this->assertTrue($slugs->contains(Role::STUDENT));
    }

    public function test_recent_users_widget_hides_the_registrar(): void
    {
        $recent = User::with('role')->withoutRegistrar()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $this->assertTrue($recent->contains('id', $this->admin->id));
        $this->assertFalse($recent->contains('id', $this->registrar->id));
    }

    public function test_registrar_row_is_still_in_the_database(): void
    {
        // The account is hidden, not deleted, so it stays recoverable.
        $this->assertDatabaseHas('users', ['id' => $this->registrar->id]);
    }
}