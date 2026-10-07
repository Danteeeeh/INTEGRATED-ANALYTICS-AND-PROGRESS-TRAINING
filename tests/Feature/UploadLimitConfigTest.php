<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Upload ceilings were hardcoded per controller and had drifted apart: a
 * profile photo allowed 2MB while a lesson material allowed 200MB. Raising a
 * limit meant finding every copy of the number.
 *
 * They now come from config so one place governs them all.
 */
class UploadLimitConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_upload_area_has_a_configured_ceiling(): void
    {
        $uploads = config('lms.uploads');

        $this->assertIsArray($uploads);

        foreach ([
            'profile_photo',
            'assignment_submission',
            'question_import',
            'learning_material',
            'module_attachment',
            'lesson_material',
            'generic',
        ] as $area) {
            $this->assertArrayHasKey($area, $uploads, "No ceiling configured for {$area}.");
            $this->assertGreaterThan(0, $uploads[$area]);
        }
    }

    public function test_ceilings_can_be_overridden_per_area(): void
    {
        config(['lms.uploads.lesson_material' => 51200]);

        $this->assertSame(51200, config('lms.uploads.lesson_material'));
        $this->assertNotSame(
            config('lms.uploads.lesson_material'),
            config('lms.uploads.assignment_submission'),
            'One area must not be able to change another area\'s ceiling.'
        );
    }

    /**
     * The 413 the instructor hit could not be reached from application code, so
     * the diagnostic has to make the mismatch legible instead.
     */
    public function test_the_check_uploads_command_reports_all_three_ceilings(): void
    {
        $this->artisan('lms:check-uploads')->assertSuccessful();

        $output = $this->artisan('lms:check-uploads')->run();

        $this->assertSame(0, $output);

        \Illuminate\Support\Facades\Artisan::call('lms:check-uploads');
        $text = \Illuminate\Support\Facades\Artisan::output();

        $this->assertStringContainsString('lesson_material', $text);
        $this->assertStringContainsString('upload_max_filesize', $text);
        $this->assertStringContainsString('client_max_body_size', $text);

        // Values are rendered for humans, not as raw byte counts.
        $this->assertStringNotContainsString('41943040', $text);
        $this->assertMatchesRegularExpression('/\d+(?:\.\d+)?\s*(?:KB|MB)/', $text);
    }
}