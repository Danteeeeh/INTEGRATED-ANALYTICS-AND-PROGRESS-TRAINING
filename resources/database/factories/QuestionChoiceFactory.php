<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionChoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionChoice>
 */
class QuestionChoiceFactory extends Factory
{
    protected $model = QuestionChoice::class;

    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'choice_text' => fake()->words(2, true),
            'points' => 0,
            'is_correct' => false,
            'position' => 0,
            'feedback' => null,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn () => ['is_correct' => true]);
    }
}