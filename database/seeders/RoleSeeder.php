<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => Role::ADMIN, 'description' => 'IT/System administrator — syncs data from AA and manages courses'],
            ['name' => 'Faculty', 'slug' => Role::INSTRUCTOR, 'description' => 'Creates modules, lessons, quizzes, assignments and gives LMS scores'],
            ['name' => 'Student', 'slug' => Role::STUDENT, 'description' => 'Enrolled learner (enrolled subjects come from AA)'],
            ['name' => 'AA Staff', 'slug' => Role::AA_STAFF, 'description' => 'Academic Officer — view-only access; official data and grades stay in AA'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
