<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MediaFile;
use App\Models\Role;
use App\Models\SubmissionFile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * §Instructor → Assignments → Submissions → Review → attached file.
 *
 * A SubmissionFile row can outlive the file it points at, and both failure
 * modes surfaced to the instructor as an unexplained 404:
 *
 *   deleted  — the MediaFile row is soft-deleted, so implicit route binding
 *              never matches it.
 *   no-bytes — the row is fine but the blob left the disk.
 */
class SubmissionAttachmentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected Course $course;

    protected Assignment $assignment;

    protected AssignmentSubmission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create(['status' => 'published']);

        $class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        $this->assignment = Assignment::create([
            'class_id' => $class->id,
            'created_by' => $this->instructor->id,
            'title' => 'Reflection Paper',
            'slug' => 'reflection-'.uniqid(),
            'points' => 100,
            'status' => 'published',
        ]);

        $this->submission = AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'submission_text' => null,
        ]);
    }

    private function attachFile(string $originalName = 'TEST FILE.pdf'): SubmissionFile
    {
        $path = "assignments/{$this->assignment->id}/submissions/{$this->submission->id}/".uniqid().'_'.$originalName;

        Storage::disk('local')->put($path, 'submitted content');

        $media = MediaFile::create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $originalName,
            'file_name' => basename($path),
            'mime_type' => 'application/pdf',
            'size' => 17,
            'extension' => 'pdf',
            'uploader_id' => $this->student->id,
        ]);

        return SubmissionFile::create([
            'assignment_submission_id' => $this->submission->id,
            'media_file_id' => $media->id,
            'original_name' => $originalName,
        ]);
    }

    private function reviewUrl(): string
    {
        return route('instructor.courses.assignments.submissions.show', [
            $this->course, $this->assignment, $this->submission,
        ]);
    }

    // ── Happy path ────────────────────────────────────────────────

    public function test_intstructor_can_open_an_intact_attachment(): void
    {
        $sf = $this->attachFile();

        $this->actingAs($this->instructor)
            ->get($this->reviewUrl())
            ->assertOk()
            ->assertSee('TEST FILE.pdf');

        $this->actingAs($this->instructor)
            ->get(route('files.download', $sf->media_file_id))
            ->assertOk();
    }

    // ── The two 404 cases ─────────────────────────────────────────

    public function test_review_page_flags_an_attachment_whose_media_row_was_deleted(): void
    {
        $sf = $this->attachFile();
        $sf->mediaFile->delete();

        $this->actingAs($this->instructor)
            ->get($this->reviewUrl())
            ->assertOk()
            ->assertSee('TEST FILE.pdf')
            ->assertSee('unavailable');

        // Crucially: no link pointing at the dead route.
        $html = $this->actingAs($this->instructor)->get($this->reviewUrl())->getContent();
        $this->assertStringNotContainsString(
            route('files.download', $sf->media_file_id),
            $html,
            'A soft-deleted media row must not render a download link.'
        );
    }

    public function test_review_page_flags_an_attachment_whose_bytes_are_gone(): void
    {
        $sf = $this->attachFile();

        // Row survives; the file leaves storage.
        Storage::disk('local')->delete($sf->mediaFile->path);

        $html = $this->actingAs($this->instructor)->get($this->reviewUrl())
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('TEST FILE.pdf', $html);
        $this->assertStringContainsString('unavailable', $html);
        $this->assertStringNotContainsString(
            route('files.download', $sf->media_file_id),
            $html,
            'A media row with no bytes must not render a download link.'
        );
    }

    public function test_downloading_a_file_missing_from_storage_explains_itself(): void
    {
        $sf = $this->attachFile();
        Storage::disk('local')->delete($sf->mediaFile->path);

        $this->actingAs($this->instructor)
            ->get(route('files.download', $sf->media_file_id))
            ->assertNotFound()
            ->assertSee('no longer available on the server');
    }

    // ── Model contract ────────────────────────────────────────────

    public function test_url_is_null_for_a_soft_deleted_media_row(): void
    {
        $sf = $this->attachFile();
        $media = $sf->mediaFile;

        $this->assertNotNull($media->url);

        $media->delete();

        $this->assertNull(
            MediaFile::withTrashed()->find($media->id)->url,
            'A trashed row must not produce a link that 404s.'
        );
    }

    public function test_file_exists_reflects_the_disk(): void
    {
        $sf = $this->attachFile();
        $media = $sf->mediaFile;

        $this->assertTrue($media->fileExists());

        Storage::disk('local')->delete($media->path);

        $this->assertFalse($media->fresh()->fileExists());
    }

    // ── The student sees the same guard ───────────────────────────

    public function test_student_submission_view_also_flags_a_dead_attachment(): void
    {
        $sf = $this->attachFile();
        $sf->mediaFile->delete();

        $html = $this->actingAs($this->student)
            ->get(route('student.courses.assignments.submissions.show', [
                $this->course, $this->assignment, $this->submission,
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('unavailable', $html);
        $this->assertStringNotContainsString(route('files.download', $sf->media_file_id), $html);
    }

    // ── Diagnostics ───────────────────────────────────────────────

    public function test_diagnostic_command_lists_broken_attachments(): void
    {
        $ok = $this->attachFile('GOOD.pdf');

        $deleted = $this->attachFile('DELETED.pdf');
        $deleted->mediaFile->delete();

        $noBytes = $this->attachFile('NOBYTES.pdf');
        Storage::disk('local')->delete($noBytes->mediaFile->path);

        $exit = Artisan::call('lms:diagnose-submission-files');
        $output = Artisan::output();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('DELETED.pdf', $output);
        $this->assertStringContainsString('NOBYTES.pdf', $output);
        $this->assertStringContainsString('soft-deleted', $output);
        $this->assertStringContainsString('no-bytes', $output);

        // A healthy attachment is never reported as broken.
        $this->assertStringNotContainsString('GOOD.pdf', $output);
        $this->assertFalse(
            MediaFile::withTrashed()->find($ok->media_file_id)->trashed(),
            'The diagnostic must not touch healthy rows.'
        );
    }

    public function test_diagnostic_fix_clears_dead_links_but_keeps_missing_bytes_row(): void
    {
        $deleted = $this->attachFile('DELETED.pdf');
        $deletedMediaId = $deleted->media_file_id;
        $deleted->mediaFile->delete();

        $noBytes = $this->attachFile('NOBYTES.pdf');
        $noBytesMediaId = $noBytes->media_file_id;
        Storage::disk('local')->delete($noBytes->mediaFile->path);

        $this->assertSame(0, Artisan::call('lms:diagnose-submission-files --fix'));

        // 'no-bytes' keeps its MediaFile row untouched so an admin can still
        // restore the blob from a backup.
        $noBytesRow = MediaFile::withTrashed()->find($noBytesMediaId);
        $this->assertNotNull($noBytesRow, 'A row whose bytes are missing must be preserved for recovery.');
        $this->assertFalse($noBytesRow->trashed(), 'A recoverable row must not be soft-deleted by --fix.');

        // An already-deleted row stays deleted, and nothing is hard-deleted.
        $deletedRow = MediaFile::withTrashed()->find($deletedMediaId);
        $this->assertNotNull($deletedRow);
        $this->assertTrue($deletedRow->trashed());
    }
}