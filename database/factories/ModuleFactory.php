<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'position' => 1,
            'is_required' => true,
            'status' => 'published',
            'created_by' => User::factory(),
        ];
    }
}
