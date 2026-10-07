<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\MediaFile;
use App\Models\ClassModel;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A lesson material stored at storage/app/public/public/lesson_materials/...
 * and downloaded through files.download returned 404.
 *
 * LessonController passed 'public/lesson_materials' as the folder *and*
 * 'disk' => 'public'. The disk already points at storage/app/public, so the
 * folder's own leading "public/" produced a doubled directory that disagreed
 * with where material uploaded before the disk option existed was written.
 */
class LessonMaterialStoragePathTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        Storage::fake('public');
        Storage::fake('local');
    }

    /** Valid payload for the lesson store route. */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Week 1 Handout',
            'lesson_type' => 'text',
            'status' => 'published',
        ], $extra);
    }

    /** @return array{0: User, 1: Course, 2: Module} */
    private function fixture(): array
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        // Course::isManagedBy() accepts the course's creator.
        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $module = Module::factory()->create(['course_id' => $course->id]);

        // LessonPolicy::create() also requires classesInstructing() to be
        // non-empty for an instructor, and isManagedBy() checks the same set.
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
        ]);

        $this->actingAs($instructor);

        return [$instructor, $course, $module];
    }

    public function test_material_is_stored_without_a_doubled_public_directory(): void
    {
        [, $course, $module] = $this->fixture();

        $this->post(
            route('instructor.courses.modules.lessons.store', [$course, $module]),
            $this->payload(['materials' => [UploadedFile::fake()->create('handout.pdf', 40, 'application/pdf')]])
        )->assertSessionHasNoErrors();

        $media = MediaFile::latest('id')->firstOrFail();

        $this->assertSame('public', $media->disk);
        $this->assertStringStartsWith('lesson_materials/', $media->path);
        $this->assertStringNotContainsString('public/', $media->path);

        // The blob must be readable at exactly the path the row records.
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_stored_material_downloads_successfully(): void
    {
        [, $course, $module] = $this->fixture();

        $this->post(
            route('instructor.courses.modules.lessons.store', [$course, $module]),
            $this->payload(['materials' => [UploadedFile::fake()->create('handout.pdf', 40, 'application/pdf')]])
        )->assertSessionHasNoErrors();

        $media = MediaFile::latest('id')->firstOrFail();

        $this->get(route('files.download', $media->id))->assertOk();
    }

    /**
     * Materials written before the 'disk' => 'public' option existed sit at
     * storage/app/public/lesson_materials/... on the local disk. Those rows must
     * not be left reading from a directory that never received them.
     */
    public function test_legacy_single_public_layout_still_resolves(): void
    {
        [, $course, $module] = $this->fixture();

        $this->post(
            route('instructor.courses.modules.lessons.store', [$course, $module]),
            $this->payload(['materials' => [UploadedFile::fake()->create('handout.pdf', 40, 'application/pdf')]])
        )->assertSessionHasNoErrors();

        $media = MediaFile::latest('id')->firstOrFail();

        // Reproduce the legacy row exactly as the old code would have written it.
        $legacyPath = 'public/lesson_materials/'.$media->file_name;
        Storage::disk('local')->put($legacyPath, 'legacy bytes');
        $media->forceFill(['disk' => 'local', 'path' => $legacyPath])->save();

        $this->assertTrue(
            $media->fresh()->fileExists(),
            'A legacy row must still find its bytes, or the instructor sees a 404.'
        );
    }
}