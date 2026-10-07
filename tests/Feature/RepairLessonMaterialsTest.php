<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\MediaFile;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lesson materials 404'd because their recorded disk/path did not match disk.
 *
 * The repair must trust where the bytes actually are rather than the recorded
 * path, must not touch healthy rows, and must not invent files that are gone.
 */
class RepairLessonMaterialsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        Storage::fake('public');
        Storage::fake('local');
    }

    /** Attach a lesson material row to a fresh lesson and return the media row. */
    private function material(string $disk, string $path, string $name = 'handout.pdf'): MediaFile
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['created_by' => $instructor->id]);

        ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
        ]);

        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $media = MediaFile::create([
            'original_name' => $name,
            'file_name' => $name,
            'path' => $path,
            'size' => 100,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'disk' => $disk,
            'uploader_id' => $instructor->id,
        ]);

        LessonMaterial::create([
            'lesson_id' => $lesson->id,
            'media_file_id' => $media->id,
            'title' => $name,
            'position' => 1,
        ]);

        return $media;
    }

    public function test_repoints_a_doubled_public_row_onto_its_real_bytes(): void
    {
        // Recorded as disk=public + "public/..." but the bytes are one level up.
        $media = $this->material('public', 'public/lesson_materials/handout.pdf');

        Storage::disk('local')->put('public/lesson_materials/handout.pdf', 'real bytes');

        Artisan::call('lms:repair-lesson-materials --apply');

        $fresh = $media->fresh();

        $this->assertSame('local', $fresh->disk);
        $this->assertSame('public/lesson_materials/handout.pdf', $fresh->path);
        $this->assertTrue($fresh->fileExists(), 'The row must end up able to find its bytes.');
    }

    public function test_dry_run_does_not_write(): void
    {
        $media = $this->material('public', 'public/lesson_materials/handout.pdf');

        Storage::disk('local')->put('public/lesson_materials/handout.pdf', 'real bytes');

        Artisan::call('lms:repair-lesson-materials');

        $this->assertStringContainsString('Dry run', Artisan::output());
        $this->assertSame('public', $media->fresh()->disk, 'Dry run must not write.');
    }

    public function test_healthy_row_is_left_alone(): void
    {
        $media = $this->material('public', 'lesson_materials/handout.pdf');

        Storage::disk('public')->put('lesson_materials/handout.pdf', 'bytes');

        Artisan::call('lms:repair-lesson-materials --apply');

        $fresh = $media->fresh();

        $this->assertSame('public', $fresh->disk);
        $this->assertSame('lesson_materials/handout.pdf', $fresh->path);
        $this->assertStringContainsString('already point at their real bytes', Artisan::output());
    }

    public function test_row_with_no_bytes_anywhere_is_reported_not_invented(): void
    {
        $media = $this->material('public', 'public/lesson_materials/gone.pdf', 'gone.pdf');

        Artisan::call('lms:repair-lesson-materials --apply');

        // Read the output once: Artisan::output() is a live buffer and successive
        // reads are not guaranteed to repeat it.
        $output = Artisan::output();

        $this->assertStringContainsString('no bytes anywhere', $output);
        $this->assertStringContainsString('gone.pdf', $output);

        // Untouched: the command must not fabricate a path for missing bytes.
        $this->assertSame('public/lesson_materials/gone.pdf', $media->fresh()->path);
    }

    public function test_new_uploads_are_reachable_and_downloadable(): void
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['created_by' => $instructor->id]);

        ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
        ]);

        $module = Module::factory()->create(['course_id' => $course->id]);

        $this->actingAs($instructor)->post(
            route('instructor.courses.modules.lessons.store', [$course, $module]),
            [
                'title' => 'Week 1',
                'lesson_type' => 'text',
                'status' => 'published',
                'materials' => [\Illuminate\Http\UploadedFile::fake()->create('handout.pdf', 40, 'application/pdf')],
            ]
        )->assertSessionHasNoErrors();

        $media = MediaFile::latest('id')->firstOrFail();

        $this->assertTrue($media->fileExists());
        $this->assertStringNotContainsString('public/', $media->path);

        $this->actingAs($instructor)
            ->get(route('files.download', $media->id))
            ->assertOk();
    }
}