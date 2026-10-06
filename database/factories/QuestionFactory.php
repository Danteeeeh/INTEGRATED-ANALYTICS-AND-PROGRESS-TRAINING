<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'question_bank_id' => QuestionBank::factory(),
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
            'difficulty' => Question::DIFFICULTY_MEDIUM,
            'default_points' => 1,
            'tags' => null,
            'created_by' => User::factory(),
            'status' => 'active',
        ];
    }

    public function type(string $type): static
    {
        return $this->state(fn () => ['question_type' => $type]);
    }
}