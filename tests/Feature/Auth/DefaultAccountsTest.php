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

    public function test_new_accounts_are_created_active_and_verified(): void
    {
        $this->seed(UserSeeder::class);

        $owner = User::where('email', 'johncedrickdayandante6@gmail.com')->firstOrFail();

        $this->assertTrue($owner->isActive());
        $this->assertNotNull($owner->email_verified_at);
    }

    public function test_redeploying_the_seeder_does_not_reactivate_or_reverify_an_existing_account(): void
    {
        $this->seed(UserSeeder::class);

        $owner = User::where('email', 'johncedrickdayandante6@gmail.com')->firstOrFail();
        $owner->status = 'inactive';
        $owner->email_verified_at = null;
        $owner->save();

        // `release.sh` runs `migrate --seed` on every deploy.
        $this->seed(UserSeeder::class);

        $fresh = $owner->fresh();
        $this->assertSame('inactive', $fresh->status);
        $this->assertNull($fresh->email_verified_at);
    }

    public function test_redeploying_the_seeder_does_not_revert_profile_edits(): void
    {
        $this->seed(UserSeeder::class);

        $owner = User::where('email', 'johncedrickdayandante6@gmail.com')->firstOrFail();
        $owner->update(['first_name' => 'Renamed', 'identifier' => 'OTP-9999']);

        $this->seed(UserSeeder::class);

        $fresh = $owner->fresh();
        $this->assertSame('Renamed', $fresh->first_name);
        $this->assertSame('OTP-9999', $fresh->identifier);
    }

    public function test_seeding_does_not_collide_with_a_soft_deleted_account(): void
    {
        $this->seed(UserSeeder::class);

        // `php artisan lms:demo-accounts delete` leaves the row, and its unique
        // email, behind. The next deploy seeds again — that must not throw.
        User::where('email', 'admin@lms.local')->firstOrFail()->delete();

        $this->seed(UserSeeder::class);

        $this->assertSoftDeleted('users', ['email' => 'admin@lms.local']);
    }
}
