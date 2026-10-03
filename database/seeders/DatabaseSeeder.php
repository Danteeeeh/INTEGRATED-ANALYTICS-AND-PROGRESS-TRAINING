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
            PhaseTwoSeeder::class, // Re-enabled - now uses firstOrCreate to avoid duplicates
            DemoDataSeeder::class, // Re-enabled - depends on PhaseTwoSeeder
            // BulkFiftySeeder::class, // Still disabled - requires sections/programs from school hierarchy
            // BulkFiftyTwoSeeder::class,
            // FiftyTopUpSeeder::class,
            // FiftyTopUpTwoSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
