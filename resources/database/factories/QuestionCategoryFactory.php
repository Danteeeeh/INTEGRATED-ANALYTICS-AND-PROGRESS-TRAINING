<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\QuestionCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionCategory>
 */
class QuestionCategoryFactory extends Factory
{
    protected $model = QuestionCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' '.fake()->numberBetween(1, 99),
            'description' => fake()->sentence(),
            'course_id' => null,
            'created_by' => User::factory()->instructor(),
        ];
    }

    public function course(Course $course): static
    {
        return $this->state(fn () => ['course_id' => $course->id]);
    }
}