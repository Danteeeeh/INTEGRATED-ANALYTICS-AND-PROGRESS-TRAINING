<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->lexify('???????')),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'objectives' => fake()->paragraph(),
            'syllabus' => fake()->paragraph(),
            'prerequisites' => fake()->optional()->sentence(),
            'duration_weeks' => fake()->numberBetween(8, 16),
            'academic_period_id' => AcademicPeriod::factory(),
            'thumbnail' => fake()->optional()->imageUrl(),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'created_by' => User::factory(),
        ];
    }
}
