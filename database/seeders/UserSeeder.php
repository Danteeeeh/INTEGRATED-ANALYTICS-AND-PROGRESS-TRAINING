<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('slug', Role::ADMIN)->firstOrFail();
        $instructor = Role::where('slug', Role::INSTRUCTOR)->firstOrFail();
        $student = Role::where('slug', Role::STUDENT)->firstOrFail();
        $aaStaff = Role::where('slug', Role::AA_STAFF)->firstOrFail();

        // Demo logins share a password that is printed in this repository and
        // they skip the email one-time code, so they are not created on a
        // public production site unless explicitly asked for. A database seeded
        // before this rule keeps them: `php artisan lms:demo-accounts` lists,
        // deactivates or soft-deletes them.
        if ($this->shouldSeedDemoAccounts()) {
            $this->seedAccount('admin@lms.local', [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'identifier' => 'ADM-0001',
                'role_id' => $admin->id,
            ]);

            $this->seedAccount('instructor@lms.local', [
                'first_name' => 'Demo',
                'last_name' => 'Instructor',
                'identifier' => 'FAC-0001',
                'role_id' => $instructor->id,
            ]);

            $this->seedAccount('student@lms.local', [
                'first_name' => 'Demo',
                'last_name' => 'Student',
                'identifier' => 'STU-0001',
                'role_id' => $student->id,
            ]);

            $this->seedAccount('aa-staff@lms.local', [
                'first_name' => 'Demo',
                'last_name' => 'AA Staff',
                'identifier' => 'AA-0001',
                'role_id' => $aaStaff->id,
            ]);
        }

        // The owner's own admin account. Not a shared demo login: it is not
        // exempt from the email one-time code.
        $this->seedAccount('johncedrickdayandante6@gmail.com', [
            'first_name' => 'John Cedrick',
            'last_name' => 'Dayandante',
            'identifier' => 'OTP-0001',
            'role_id' => $admin->id,
        ]);
    }

    /**
     * Create the account the first time and leave it alone afterwards.
     *
     * `release.sh` runs `migrate --seed` on every deploy, so an existing row is
     * never rewritten: a password, name, role, status or verification that an
     * administrator changed has to survive the deploy. Soft-deleted rows count
     * as existing too — after `lms:demo-accounts delete`, re-inserting the same
     * email would violate the unique index and fail the release.
     */
    private function seedAccount(string $email, array $attributes): void
    {
        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        $user = new User($attributes + [
            'email' => $email,
            'password' => Hash::make(config('lms.dev_seed_password') ?: 'Password123!'),
            'status' => 'active',
        ]);

        // Not mass-assignable, so it is set directly.
        $user->email_verified_at = now();
        $user->save();
    }

    private function shouldSeedDemoAccounts(): bool
    {
        $configured = config('lms.seed_demo_users');

        return $configured === null || $configured === ''
            ? ! app()->environment('production')
            : filter_var($configured, FILTER_VALIDATE_BOOLEAN);
    }
}
