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
            DepartmentProgramSeeder::class, // Mock department/program/section data
            PhaseTwoSeeder::class, // Re-enabled - now uses firstOrCreate to avoid duplicates
            DemoDataSeeder::class, // Re-enabled - depends on PhaseTwoSeeder
            // BulkFiftySeeder::class, // Permanently disabled - HostForge deployment sync issues
            // BulkFiftyTwoSeeder::class,
            // FiftyTopUpSeeder::class,
            // FiftyTopUpTwoSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
