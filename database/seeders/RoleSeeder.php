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
            ['name' => 'Registrar / Staff', 'slug' => Role::REGISTRAR, 'description' => 'Student records and enrollment management'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
