<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DefaultAccountsTest extends TestCase
{
    public function test_default_accounts_and_otp_test_account_are_seeded_with_expected_access(): void
    {
        $this->seed(UserSeeder::class);
        $expected = [
            'admin@lms.local' => Role::ADMIN,
            'instructor@lms.local' => Role::INSTRUCTOR,
            'student@lms.local' => Role::STUDENT,
            'johncedrickdayandante6@gmail.com' => Role::ADMIN,
        ];
        foreach ($expected as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertTrue($user->isActive());
            $this->assertSame($role, $user->role?->slug);
            $this->assertTrue(Hash::check('Password123!', $user->password));
        }
    }

    public function test_local_default_accounts_bypass_otp(): void
    {
        config()->set('lms.login_otp.enabled', true);
        Mail::fake();
        $this->seed(UserSeeder::class);

        $accounts = [
            'admin@lms.local' => 'admin.dashboard',
            'instructor@lms.local' => 'instructor.dashboard',
            'student@lms.local' => 'student.dashboard',
        ];

        foreach ($accounts as $email => $dashboard) {
            Auth::logout();
            $response = $this->post(route('login'), [
                'email' => $email,
                'password' => 'Password123!',
            ]);

            $response->assertRedirect(route($dashboard));
            $this->assertAuthenticatedAs(User::where('email', $email)->firstOrFail());
        }

        Mail::assertNothingSent();
        $this->assertDatabaseCount('login_verifications', 0);
    }
}
