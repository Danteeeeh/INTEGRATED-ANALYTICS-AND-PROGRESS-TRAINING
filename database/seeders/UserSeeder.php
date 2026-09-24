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
        $admin = Role::where('slug', Role::ADMIN)->first();
        $instructor = Role::where('slug', Role::INSTRUCTOR)->first();
        $student = Role::where('slug', Role::STUDENT)->first();
        $registrar = Role::where('slug', Role::REGISTRAR)->first();

        User::updateOrCreate(
            ['email' => 'admin@lms.local'],
            [
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'identifier' => 'ADM-0001',
                'password' => Hash::make('Password123!'),
                'role_id' => $admin->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'instructor@lms.local'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Instructor',
                'identifier' => 'FAC-0001',
                'password' => Hash::make('Password123!'),
                'role_id' => $instructor->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'registrar@lms.local'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Registrar',
                'identifier' => 'REG-0001',
                'password' => Hash::make('Password123!'),
                'role_id' => $registrar->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'student@lms.local'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Student',
                'identifier' => 'STU-0001',
                'password' => Hash::make('Password123!'),
                'role_id' => $student->id,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
