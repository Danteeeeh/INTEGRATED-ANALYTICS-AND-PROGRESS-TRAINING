<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassModelFactory extends Factory
{
    protected $model = ClassModel::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->lexify('???????')),
            'course_id' => Course::factory(),
            // An instructor, not the default student. ClassModelFactory previously used
            // a bare User::factory(), so every test class had a "student"
            // teaching it.
            'instructor_id' => User::factory()->instructor(),
            'academic_period_id' => AcademicPeriod::factory(),
            'schedule' => fake()->optional()->sentence(),
            'room' => fake()->optional()->word(),
            'capacity' => fake()->numberBetween(20, 50),
            'status' => fake()->randomElement(['active', 'inactive', 'cancelled']),
        ];
    }
}
