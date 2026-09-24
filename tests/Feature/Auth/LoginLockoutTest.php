<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
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

    protected function makeActiveUser(string $email = 'test@example.com'): User
    {
        $role = Role::where('slug', Role::STUDENT)->firstOrFail();

        return User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password123'),
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    public function test_account_is_locked_after_max_failed_attempts(): void
    {
        $this->makeActiveUser();

        config()->set('lms.login_max_attempts', 3);
        config()->set('lms.login_lockout_minutes', 15);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(3, $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
        $this->assertTrue(now()->lessThan($user->locked_until));

        // Even with the correct password the account must stay locked.
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors();
        $response->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_locked_account_shows_remaining_lock_time_message(): void
    {
        $this->makeActiveUser();

        config()->set('lms.login_max_attempts', 2);
        config()->set('lms.login_lockout_minutes', 15);

        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong']);
        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong']);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $message = session('errors')->first('email');
        $this->assertStringContainsString('Too many failed login attempts', $message);
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        $this->makeActiveUser();

        // Two failed attempts, then a successful one.
        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong']);
        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong']);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
        $this->assertNull($user->last_failed_login_at);
    }

    public function test_expired_lock_allows_login_again(): void
    {
        $user = $this->makeActiveUser();

        config()->set('lms.login_max_attempts', 3);
        config()->set('lms.login_lockout_minutes', 15);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong']);
        }

        // Simulate the lock window expiring.
        $user->forceFill(['locked_until' => now()->subMinute()])->save();

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticated();

        $user->refresh();
        $this->assertSame(0, $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_api_login_is_locked_after_max_failed_attempts(): void
    {
        $this->makeActiveUser();

        config()->set('lms.login_max_attempts', 3);
        config()->set('lms.login_lockout_minutes', 15);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_inactive_session_is_logged_out(): void
    {
        $user = $this->makeActiveUser();

        config()->set('lms.session_inactivity_timeout', 30);

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(31)->timestamp])
            ->get(route('student.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }

    public function test_recent_activity_keeps_session_alive(): void
    {
        $user = $this->makeActiveUser();

        config()->set('lms.session_inactivity_timeout', 30);

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(5)->timestamp])
            ->get(route('student.dashboard'))
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_keep_alive_refreshes_session_activity(): void
    {
        $user = $this->makeActiveUser();

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(29)->timestamp])
            ->postJson(route('session.keep-alive'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_session_is_logged_out_from_file_download_route(): void
    {
        $user = $this->makeActiveUser();
        $mediaFile = \App\Models\MediaFile::factory()->create([
            'disk' => 'local',
            'path' => 'test-files/inactive-session.txt',
        ]);

        config()->set('lms.session_inactivity_timeout', 30);

        $this->actingAs($user)
            ->withSession(['last_activity_at' => now()->subMinutes(31)->timestamp])
            ->get(route('files.download', $mediaFile))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertGuest();
    }
}
