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
        $roleId = Role::where('slug', Role::STUDENT)->value('id')
            ?? (Role::factory()->create()->id);

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'identifier' => strtoupper(Str::random(8)),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role_id' => $roleId,
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }
}
