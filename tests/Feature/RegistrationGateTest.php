<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public self-registration must be closed by default.
 *
 * Student accounts are authoritative in AA and arrive through the sync; an
 * open form would let anybody create an active student account with no
 * approval, which is the largest pre-publish risk in this LMS.
 */
class RegistrationGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function payload(string $email = 'self.signup@example.com'): array
    {
        return [
            'first_name' => 'Self',
            'last_name' => 'Signup',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];
    }

    public function test_registration_is_off_by_default(): void
    {
        $this->assertFalse(config('lms.registration_enabled'));

        $this->get('/register')->assertNotFound();

        $this->post('/register', $this->payload())->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'self.signup@example.com']);
    }

    public function test_registration_can_be_enabled_explicitly(): void
    {
        config(['lms.registration_enabled' => true]);

        $this->get('/register')->assertOk();

        $this->post('/register', $this->payload())
            ->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticated();

        $user = User::where('email', 'self.signup@example.com')->firstOrFail();

        $this->assertSame('active', $user->status);
        $this->assertSame('student', $user->role->slug);
    }
}
