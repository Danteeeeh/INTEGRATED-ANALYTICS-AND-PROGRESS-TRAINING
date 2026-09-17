<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    private static int $factoryCounter = 0;

    public function definition(): array
    {
        $reserved = [Role::ADMIN, Role::INSTRUCTOR, Role::STUDENT];

        do {
            self::$factoryCounter++;
            $counter = self::$factoryCounter;
            $name = fake()->unique()->word().$counter;
            $slug = Str::slug($name);
        } while (in_array($slug, $reserved, true) || Role::where('slug', $slug)->exists());

        return [
            'name' => ucfirst(str_replace($counter, ' '.$counter, $name)),
            'slug' => $slug,
            'description' => fake()->sentence(),
        ];
    }
}
