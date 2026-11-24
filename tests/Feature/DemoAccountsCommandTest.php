<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `php artisan lms:demo-accounts` removes the shared demo logins from a
 * database that was seeded before UserSeeder stopped creating them. These
 * tests pin down the safety rails: preview is read-only, only the four known
 * addresses are touched, and the last administrator is never removed.
 */
class DemoAccountsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /** @return array<int, User> */
    private function demoAccounts(): array
    {
        return [
            User::factory()->admin()->create(['email' => 'admin@lms.local', 'status' => 'active']),
            User::factory()->instructor()->create(['email' => 'instructor@lms.local', 'status' => 'active']),
            User::factory()->student()->create(['email' => 'student@lms.local', 'status' => 'active']),
            User::factory()->aaStaff()->create(['email' => 'aa-staff@lms.local', 'status' => 'active']),
        ];
    }

    public function test_preview_is_the_default_and_changes_nothing(): void
    {
        $demo = $this->demoAccounts();
        User::factory()->admin()->create(['email' => 'owner@school.test', 'status' => 'active']);

        $this->artisan('lms:demo-accounts')
            ->expectsOutputToContain('Preview only')
            ->assertExitCode(0);

        foreach ($demo as $user) {
            $this->assertSame('active', $user->fresh()->status);
            $this->assertNotSoftDeleted($user);
        }
    }

    public function test_deactivate_blocks_only_the_demo_accounts(): void
    {
        $demo = $this->demoAccounts();
        $owner = User::factory()->admin()->create(['email' => 'owner@school.test', 'status' => 'active']);
        $realStudent = User::factory()->student()->create(['email' => 'student@school.test', 'status' => 'active']);

        $this->artisan('lms:demo-accounts', ['action' => 'deactivate', '--force' => true])
            ->assertExitCode(0);

        foreach ($demo as $user) {
            $this->assertSame('inactive', $user->fresh()->status);
            $this->assertNotSoftDeleted($user);
        }

        $this->assertSame('active', $owner->fresh()->status);
        $this->assertSame('active', $realStudent->fresh()->status);
    }

    public function test_delete_soft_deletes_only_the_demo_accounts(): void
    {
        $demo = $this->demoAccounts();
        $owner = User::factory()->admin()->create(['email' => 'owner@school.test', 'status' => 'active']);

        $this->artisan('lms:demo-accounts', ['action' => 'delete', '--force' => true])
            ->assertExitCode(0);

        foreach ($demo as $user) {
            $this->assertSoftDeleted($user);
        }

        $this->assertNotSoftDeleted($owner);
    }

    public function test_it_refuses_to_remove_the_last_active_administrator(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@lms.local', 'status' => 'active']);
        User::factory()->student()->create(['email' => 'student@lms.local', 'status' => 'active']);

        $this->artisan('lms:demo-accounts', ['action' => 'deactivate', '--force' => true])
            ->expectsOutputToContain('last active administrator')
            ->assertExitCode(1);

        $this->assertSame('active', $admin->fresh()->status);
        $this->assertNotSoftDeleted($admin);
    }

    public function test_it_never_matches_lookalike_addresses(): void
    {
        $lookalike = User::factory()->student()->create(['email' => 'admin@lms.local.example', 'status' => 'active']);
        $otherDomain = User::factory()->student()->create(['email' => 'student@lms.local.ph', 'status' => 'active']);

        $this->artisan('lms:demo-accounts', ['action' => 'delete', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame('active', $lookalike->fresh()->status);
        $this->assertNotSoftDeleted($otherDomain);
    }

    public function test_rotate_password_changes_only_the_demo_credentials(): void
    {
        $demo = $this->demoAccounts();
        $owner = User::factory()->admin()->create(['email' => 'owner@school.test', 'status' => 'active']);

        $before = [];
        foreach ($demo as $user) {
            $before[$user->email] = $user->password;
        }
        $ownerBefore = $owner->password;

        // A demo account that is still signed in must be signed out.
        $demo[1]->createToken('phone');

        $this->artisan('lms:demo-accounts', ['action' => 'rotate-password', '--force' => true])
            ->assertExitCode(0);

        foreach ($demo as $user) {
            $fresh = $user->fresh();

            $this->assertNotSame($before[$user->email], $fresh->password);
            $this->assertSame('active', $fresh->status, 'Rotation must not change the status.');
        }

        $this->assertSame($ownerBefore, $owner->fresh()->password);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $demo[1]->id]);
    }

    public function test_rotate_password_asks_before_changing_anything(): void
    {
        $demo = $this->demoAccounts();
        $before = $demo[0]->password;

        $this->artisan('lms:demo-accounts', ['action' => 'rotate-password'])
            ->expectsConfirmation(
                'Rotate the password of 4 demo account(s)? Their current sessions and API tokens will be signed out.',
                'no'
            )
            ->assertExitCode(0);

        $this->assertSame($before, $demo[0]->fresh()->password);
    }

    public function test_an_unknown_action_is_rejected(): void
    {
        $this->artisan('lms:demo-accounts', ['action' => 'nuke'])->assertExitCode(2);
    }
}
