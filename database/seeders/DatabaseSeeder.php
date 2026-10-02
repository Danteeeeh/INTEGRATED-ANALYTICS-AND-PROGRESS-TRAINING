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
            PhaseTwoSeeder::class,
            DemoDataSeeder::class,
            BulkFiftySeeder::class,
            BulkFiftyTwoSeeder::class,
            FiftyTopUpSeeder::class,
            FiftyTopUpTwoSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
