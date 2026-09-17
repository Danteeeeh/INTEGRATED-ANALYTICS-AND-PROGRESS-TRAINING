<?php

namespace Database\Factories;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'class_id' => ClassModel::factory(),
            'status' => fake()->randomElement(['pending', 'active', 'completed', 'dropped']),
            'final_grade' => fake()->optional(0.7, null)->randomFloat(1, 0, 100),
            'notes' => fake()->optional()->sentence(),
            'enrolled_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'completed_at' => fake()->optional(0.3)->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
