<?php

namespace Database\Factories;

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaFile>
 */
class MediaFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ext = fake()->randomElement(['pdf', 'docx', 'pptx', 'mp4', 'mp3', 'txt']);

        return [
            'disk' => 'public',
            'path' => 'test-files/' . fake()->uuid() . '.' . $ext,
            'original_name' => fake()->words(3, true) . '.' . $ext,
            'file_name' => fake()->uuid() . '.' . $ext,
            'mime_type' => 'application/' . $ext,
            'size' => fake()->numberBetween(1024, 1048576),
            'extension' => $ext,
            'uploader_id' => User::factory()->instructor(),
        ];
    }
}
