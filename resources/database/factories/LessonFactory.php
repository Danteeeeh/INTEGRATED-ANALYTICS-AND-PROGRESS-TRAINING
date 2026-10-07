<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'content' => fake()->optional()->paragraphs(2, true),
            'duration_minutes' => fake()->optional()->numberBetween(5, 60),
            'position' => 1,
            'lesson_type' => 'text',
            'is_required' => true,
            'status' => 'published',
            'created_by' => User::factory()->instructor(),
        ];
    }
}
