<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\MediaFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonMaterial>
 */
class LessonMaterialFactory extends Factory
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
            'media_file_id' => MediaFile::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'position' => 1,
            'is_required' => true,
        ];
    }
}
