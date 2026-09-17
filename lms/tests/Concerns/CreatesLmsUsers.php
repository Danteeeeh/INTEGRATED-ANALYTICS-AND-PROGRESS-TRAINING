<?php

namespace Tests\Concerns;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

trait CreatesLmsUsers
{
    protected function seedRbac(): void
    {
        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    protected function makeUser(string $roleSlug, array $attributes = []): User
    {
        $this->seedRbac();

        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'status' => 'active',
        ], $attributes));
    }
}
