<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
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

    public function test_user_can_login_with_valid_credentials(): void
    {
        $role = Role::where('slug', Role::STUDENT)->firstOrFail();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $role = Role::where('slug', Role::STUDENT)->firstOrFail();
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $role = Role::where('slug', Role::STUDENT)->firstOrFail();
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'inactive',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertStatus(302);
        $response->assertSessionHasErrors();
    }

    public function test_admin_can_login_and_access_admin_dashboard(): void
    {
        $role = Role::where('slug', Role::ADMIN)->firstOrFail();
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertStatus(200);
    }

    public function test_instructor_can_login_and_access_instructor_dashboard(): void
    {
        $role = Role::where('slug', Role::INSTRUCTOR)->firstOrFail();
        $user = User::factory()->create([
            'email' => 'instructor@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'instructor@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('instructor.dashboard'));

        $this->actingAs($user)
            ->get(route('instructor.dashboard'))
            ->assertStatus(200);
    }

    public function test_student_can_login_and_access_student_dashboard(): void
    {
        $role = Role::where('slug', Role::STUDENT)->firstOrFail();
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('student.dashboard'));

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertStatus(200);
    }

    public function test_registrar_can_login_and_access_registrar_dashboard(): void
    {
        $role = Role::where('slug', Role::REGISTRAR)->firstOrFail();
        $user = User::factory()->create([
            'email' => 'registrar@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'registrar@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('registrar.dashboard'));

        $this->actingAs($user)
            ->get(route('registrar.dashboard'))
            ->assertStatus(200);
    }

    public function test_user_can_logout(): void
    {
        $role = Role::where('slug', Role::STUDENT)->firstOrFail();
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,  // FIXED: Added missing role_id
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $role = Role::where('slug', Role::STUDENT)->firstOrFail();
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        // FIXED: Properly simulate rate limiting
        // Note: You may need to adjust the number based on your throttle configuration
        // Typically Laravel uses 5 attempts per minute by default

        // Clear any previous attempts
        $this->artisan('cache:clear');

        // Make 5 failed attempts (just below the limit if default is 5)
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // The 6th attempt should trigger rate limiting
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }
}
