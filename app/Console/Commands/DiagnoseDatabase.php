<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Role;

class DiagnoseDatabase extends Command
{
    protected $signature = 'db:diagnose';
    protected $description = 'Diagnose database connection and user data for login issues';

    public function handle(): int
    {
        $this->info('=== Database Diagnostics ===');

        // 1. Check database connection
        $this->info('\n1. Checking database connection...');
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful');
            $this->info('   Database: ' . DB::connection()->getDatabaseName());
            $this->info('   Connection: ' . DB::connection()->getName());
        } catch (\Exception $e) {
            $this->error('❌ Database connection failed: ' . $e->getMessage());
            return 1;
        }

        // 2. Check if users table exists
        $this->info('\n2. Checking if users table exists...');
        if (Schema::hasTable('users')) {
            $this->info('✅ users table exists');
        } else {
            $this->error('❌ users table does NOT exist - migrations may not have run');
            $this->warn('   Run: php artisan migrate --force');
            return 1;
        }

        // 3. Check if roles table exists
        $this->info('\n3. Checking if roles table exists...');
        if (Schema::hasTable('roles')) {
            $this->info('✅ roles table exists');
        } else {
            $this->error('❌ roles table does NOT exist');
            return 1;
        }

        // 4. Count users
        $this->info('\n4. Checking user records...');
        $userCount = User::count();
        $this->info('   Total users in database: ' . $userCount);

        if ($userCount === 0) {
            $this->error('❌ No users found in database');
            $this->warn('   Run: php artisan db:seed --force');
            return 1;
        }

        // 5. List all users
        $this->info('\n5. User accounts in database:');
        $users = User::with('role')->get();
        foreach ($users as $user) {
            $this->info("   - Email: {$user->email}");
            $this->info("     Name: {$user->first_name} {$user->last_name}");
            $this->info("     Role: {$user->role?->slug ?? 'N/A'}");
            $this->info("     Status: {$user->status}");
            $this->info("     Password Hash: " . substr($user->password, 0, 20) . '...');
            $this->info('');
        }

        // 6. Check specific seeded accounts
        $this->info('\n6. Checking seeded accounts...');
        $seededEmails = [
            'admin@lms.local',
            'instructor@lms.local',
            'student@lms.local',
            'johncedrickdayandante6@gmail.com',
        ];

        foreach ($seededEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $this->info("✅ {$email} - exists");
            } else {
                $this->error("❌ {$email} - NOT FOUND");
            }
        }

        // 7. Test password verification
        $this->info('\n7. Testing password verification for seeded accounts...');
        foreach ($seededEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $isValid = \Illuminate\Support\Facades\Hash::check('Password123!', $user->password);
                if ($isValid) {
                    $this->info("✅ {$email} - password 'Password123!' is valid");
                } else {
                    $this->error("❌ {$email} - password 'Password123!' is INVALID");
                }
            }
        }

        $this->info('\n=== Diagnostics Complete ===');
        return 0;
    }
}
