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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploading a file from the lesson Materials page failed with
 * "Error uploading files: Undefined variable $suspiciousPatterns".
 *
 * FileUploadService looped over $suspiciousPatterns, but the property was never
 * declared, so every upload that reached the content scan died. PDFs appeared to
 * work only when the scan returned early.
 */
class LessonMaterialUploadEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private Course $course;

    private Module $module;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        Storage::fake('public');

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create([
            'created_by' => $this->instructor->id,
            'status' => 'published',
        ]);

        ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
        ]);

        $this->module = Module::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'published',
        ]);

        $this->lesson = Lesson::factory()->create([
            'module_id' => $this->module->id,
            'status' => 'published',
        ]);

        $this->actingAs($this->instructor);
    }

    private function upload(array $files, array $extra = [])
    {
        return $this->post(
            route('instructor.courses.modules.lessons.materials.upload', [
                $this->course, $this->module, $this->lesson,
            ]),
            array_merge(['files' => $files], $extra)
        );
    }

    public function test_upload_endpoint_succeeds_for_a_pdf(): void
    {
        $response = $this->upload([
            UploadedFile::fake()->create('handout.pdf', 20, 'application/pdf'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertSame(1, LessonMaterial::where('lesson_id', $this->lesson->id)->count());

        $media = MediaFile::latest('id')->firstOrFail();
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_upload_endpoint_succeeds_for_a_docx(): void
    {
        // The reported failure: the content scan ran and hit the missing
        // property, so nothing of this size or type could be uploaded.
        $response = $this->upload([
            UploadedFile::fake()->create(
                'notes.docx',
                20,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ),
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
    }

    public function test_upload_endpoint_reports_a_readable_error_instead_of_crashing(): void
    {
        // A rejected file must come back as a message the UI can show, never a
        // 500 the instructor cannot act on.
        $response = $this->upload([
            UploadedFile::fake()->create('evil.php', 2, 'text/plain'),
        ]);

        $this->assertTrue(
            $response->status() !== 500,
            'The endpoint must not blow up into a 500: got '.$response->status()
        );
    }

    public function test_multiple_files_upload_in_one_request(): void
    {
        $response = $this->upload([
            UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->create('b.pdf', 10, 'application/pdf'),
        ]);

        $response->assertOk();

        $this->assertSame(2, LessonMaterial::where('lesson_id', $this->lesson->id)->count());
    }
}