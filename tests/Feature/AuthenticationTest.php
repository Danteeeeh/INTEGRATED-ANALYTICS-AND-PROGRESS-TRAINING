<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected function makeUser(string $roleSlug, string $status = 'active'): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucfirst($roleSlug)]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => $status,
        ]);
    }

    public function test_admin_can_login_and_reach_admin_dashboard(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user)->assertAuthenticatedAs($user);
    }

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_instructor_cannot_access_student_dashboard(): void
    {
        $instructor = $this->makeUser('instructor');

        $response = $this->actingAs($instructor)->get('/student/dashboard');

        $response->assertForbidden();
    }

    public function test_api_login_returns_sanctum_token(): void
    {
        $user = $this->makeUser('student');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['data' => ['user', 'token']]);
    }
}
