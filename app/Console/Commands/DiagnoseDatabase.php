<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseDatabase extends Command
{
    protected $signature = 'db:diagnose';

    protected $description = 'Diagnose database connection, schema, and user counts for login issues';

    public function handle(): int
    {
        $this->info('=== Database Diagnostics ===');

        // 1. Check database connection
        $this->info("\n1. Checking database connection...");
        try {
            DB::connection()->getPdo();
            $this->info('✅ Database connection successful');
            $this->info('   Driver: '.DB::connection()->getDriverName());
        } catch (\Exception) {
            // Connection exceptions can contain host, database, and username
            // details. Keep the command output safe to paste into a ticket.
            $this->error('❌ Database connection failed.');

            return 1;
        }

        // 2. Check if users table exists
        $this->info("\n2. Checking if users table exists...");
        if (! Schema::hasTable('users')) {
            $this->error('❌ users table does NOT exist - migrations may not have run');
            $this->warn('   Run: php artisan migrate --force');

            return 1;
        }
        $this->info('✅ users table exists');

        // 3. Check if roles table exists
        $this->info("\n3. Checking if roles table exists...");
        if (! Schema::hasTable('roles')) {
            $this->error('❌ roles table does NOT exist');

            return 1;
        }
        $this->info('✅ roles table exists');

        // 4. Count users
        $this->info("\n4. Checking user records...");
        $userCount = User::count();
        $this->info('   Total users in database: '.$userCount);

        if ($userCount === 0) {
            $this->error('❌ No users found in database');
            $this->warn('   Run: php artisan db:seed --force');

            return 1;
        }

        // 5. Non-sensitive status counts only. Never print emails, names,
        // hashes, passwords, or individual account records.
        $this->info("\n5. User counts by status:");
        foreach (['active', 'inactive', 'pending'] as $status) {
            $this->info('   '.ucfirst($status).': '.User::where('status', $status)->count());
        }

        $this->info("\n=== Diagnostics Complete ===");

        return 0;
    }
}
