<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
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
 * An attached file could only be downloaded, never opened, and its name was
 * rendered from the slugified storage name — which Str::slug() empties entirely
 * for a non-latin script, leaving boxes on screen.
 *
 * Both sides must show the name the uploader typed and offer a preview.
 */
class FilePreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private Course $course;

    private ClassModel $class;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        Storage::fake('public');
        Storage::fake('local');

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create([
            'created_by' => $this->instructor->id,
            'status' => 'published',
        ]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
        ]);

        $this->module = Module::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'published',
        ]);

        Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);
    }

    /** @return array{0: \App\Models\MediaFile, 1: \App\Models\ModuleAttachment} */
    private function attachPdf(): array
    {
        $response = $this->actingAs($this->instructor)->post(
            route('instructor.courses.modules.attachments.upload', [$this->course, $this->module]),
            ['file' => UploadedFile::fake()->create('Lesson Plan.pdf', 30, 'application/pdf')]
        );

        $response->assertRedirect();

        $media = \App\Models\MediaFile::latest('id')->firstOrFail();

        return [$media, $this->module->attachments()->latest('id')->firstOrFail()];
    }

    public function test_attached_pdf_is_previewable_and_downloadable_on_the_module_page(): void
    {
        [$media, $attachment] = $this->attachPdf();

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.courses.modules.show', [$this->course, $this->module]));

        $response->assertOk();

        // A preview control, wired to the file.
        $response->assertSee('data-file-viewer-open', false);
        $response->assertSee(route('files.serve', ['mediaFile' => $media->id]), false);
        $response->assertSee(route('files.download', ['mediaFile' => $media->id]), false);

        // The name the uploader typed, not the slugified storage name.
        $response->assertSee('Lesson Plan.pdf');
    }

    public function test_serve_returns_the_file_inline_so_a_pdf_renders_in_place(): void
    {
        [$media] = $this->attachPdf();

        $response = $this->actingAs($this->instructor)
            ->get(route('files.serve', ['mediaFile' => $media->id]));

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'inline');
    }

    public function test_a_file_whose_bytes_are_missing_reports_it_instead_of_crashing(): void
    {
        [$media] = $this->attachPdf();

        // The row survives, the blob does not.
        Storage::disk($media->disk)->delete($media->path);

        $this->actingAs($this->instructor)
            ->get(route('files.serve', ['mediaFile' => $media->id]))
            ->assertNotFound();

        // And the module page says so rather than offering a dead viewer.
        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.modules.show', [$this->course, $this->module]))
            ->assertOk()
            ->assertSee('Contents missing from storage');
    }

    public function test_student_can_preview_a_lesson_material(): void
    {
        $lesson = \App\Models\Lesson::factory()->create([
            'module_id' => $this->module->id,
            'status' => 'published',
        ]);

        $media = app(\App\Services\FileUploadService::class)->uploadFile(
            UploadedFile::fake()->create('Handout.pdf', 20, 'application/pdf'),
            'lesson_materials',
            ['disk' => 'public']
        );

        \App\Models\LessonMaterial::create([
            'lesson_id' => $lesson->id,
            'media_file_id' => $media->id,
            'title' => null,   // force the fallback chain to original_name
            'position' => 1,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.modules.lessons.show', [$this->course, $this->module, $lesson]));

        $response->assertOk();
        $response->assertSee('data-file-viewer-open', false);
        $response->assertSee('Handout.pdf');
    }

    /**
     * The storage name is generated with Str::slug(), which silently drops
     * anything it cannot transliterate. The name the uploader typed is stored
     * separately and must be what the UI shows.
     */
    public function test_a_non_latin_filename_still_renders_readable(): void
    {
        [$media] = $this->attachPdf();

        // Simulate a name the slugger could not transliterate at all.
        $media->forceFill(['original_name' => '文書ファイル', 'file_name' => '_20261007.pdf'])->save();

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.modules.show', [$this->course, $this->module]))
            ->assertOk()
            ->assertSee('文書ファイル');
    }

    public function test_generated_storage_name_is_never_left_blank(): void
    {
        $service = app(\App\Services\FileUploadService::class);

        $media = $service->uploadFile(
            UploadedFile::fake()->create('文書ファイル.docx', 5,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            'lesson_materials',
            ['disk' => 'public']
        );

        $this->assertNotSame('', $media->file_name);
        $this->assertStringStartsNotWith('_', $media->file_name, 'A slugged name must not collapse to nothing.');
        $this->assertSame('文書ファイル.docx', $media->original_name);
    }
}
