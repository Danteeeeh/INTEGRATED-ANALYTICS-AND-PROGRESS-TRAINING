<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonProgress>
 */
class LessonProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'student_id' => User::factory(),
            'status' => fake()->randomElement(['not_started', 'in_progress', 'completed']),
            'progress_percent' => fake()->numberBetween(0, 100),
            'total_seconds' => fake()->numberBetween(0, 3600),
            'started_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'last_accessed_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'completed_at' => null,
        ];
    }
}
