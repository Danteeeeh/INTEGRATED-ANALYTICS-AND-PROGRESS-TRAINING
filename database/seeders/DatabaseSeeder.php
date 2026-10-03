<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            UserSeeder::class,
            // PhaseTwoSeeder::class, // Temporarily disabled due to deployment sync issues
            // DemoDataSeeder::class,
            // BulkFiftySeeder::class, // Temporarily disabled due to deployment sync issues
            // BulkFiftyTwoSeeder::class,
            // FiftyTopUpSeeder::class,
            // FiftyTopUpTwoSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
