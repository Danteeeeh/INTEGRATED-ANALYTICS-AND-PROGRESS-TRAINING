<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => Role::ADMIN, 'description' => 'Institutional administrator'],
            ['name' => 'Instructor', 'slug' => Role::INSTRUCTOR, 'description' => 'Manages assigned academic content'],
            ['name' => 'Student', 'slug' => Role::STUDENT, 'description' => 'Enrolled learner'],
            ['name' => 'Registrar', 'slug' => Role::REGISTRAR, 'description' => 'Manages student records and enrollments'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
