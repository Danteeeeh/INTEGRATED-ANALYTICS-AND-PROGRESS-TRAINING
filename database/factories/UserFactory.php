<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'identifier' => strtoupper(Str::random(8)),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => fn () => $this->roleIdFor(Role::STUDENT),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Resolve a role id without creating a duplicate role row each time.
     */
    protected function roleIdFor(string $slug): int
    {
        return Role::where('slug', $slug)->value('id')
            ?? Role::create(['name' => ucfirst($slug), 'slug' => $slug])->id;
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role_id' => $this->roleIdFor(Role::ADMIN)]);
    }

    public function instructor(): static
    {
        return $this->state(fn () => ['role_id' => $this->roleIdFor(Role::INSTRUCTOR)]);
    }

    public function student(): static
    {
        return $this->state(fn () => ['role_id' => $this->roleIdFor(Role::STUDENT)]);
    }

    public function registrar(): static
    {
        return $this->state(fn () => ['role_id' => $this->roleIdFor(Role::REGISTRAR)]);
    }
}
