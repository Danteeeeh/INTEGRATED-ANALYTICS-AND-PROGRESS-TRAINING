<?php

namespace Database\Factories;

use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionBank>
 */
class QuestionBankFactory extends Factory
{
    protected $model = QuestionBank::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(3, true).' Bank',
            'code' => strtoupper(fake()->unique()->bothify('QB-##??')),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement(['Biology', 'Mathematics', 'History', 'Computer Science']),
            'course_id' => null,
            'class_id' => null,
            'created_by' => User::factory()->instructor(),
            'is_shared' => false,
            'status' => 'active',
        ];
    }

    public function shared(): static
    {
        return $this->state(fn () => ['is_shared' => true]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft']);
    }
}