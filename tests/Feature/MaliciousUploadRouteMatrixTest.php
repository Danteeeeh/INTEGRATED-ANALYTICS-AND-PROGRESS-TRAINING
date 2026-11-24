<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Route-level matrix for every upload surface. A rejected file must not
 * leave a MediaFile row, a feature record, or stored bytes behind.
 */
class MaliciousUploadRouteMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const SUSPICIOUS_MESSAGE = 'The file contains suspicious content and cannot be uploaded.';

    private User $admin;

    private User $instructor;

    private User $student;

    private Course $course;

    private ClassModel $class;

    private Module $module;

    private Lesson $lesson;

    private Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        Storage::fake('public');
        Storage::fake('local');

        $this->admin = User::factory()->admin()->create(['status' => 'active']);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create([
            'created_by' => $this->instructor->id,
            'status' => 'published',
        ]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => 'active',
            'enrolled_at' => now()->subDay(),
        ]);

        $this->module = Module::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'published',
        ]);

        $this->lesson = Lesson::factory()->create([
            'module_id' => $this->module->id,
            'status' => 'published',
        ]);

        $this->assignment = Assignment::create([
            'class_id' => $this->class->id,
            'created_by' => $this->instructor->id,
            'title' => 'Unsafe upload check',
            'slug' => 'unsafe-upload-'.uniqid(),
            'instructions' => 'Submit a file.',
            'points' => 100,
            'submission_type' => Assignment::TYPE_FILE,
            'due_date' => now()->addDay(),
            'allow_late' => true,
            'status' => Assignment::STATUS_PUBLISHED,
        ]);
    }

    public function test_profile_photo_upload_rejects_unsafe_content_without_replacing_the_photo(): void
    {
        $this->student->update(['profile_photo_path' => 'profile-photos/current.png']);
        Storage::disk('public')->put('profile-photos/current.png', 'existing photo');

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        $response = $this->actingAs($this->student)->put(route('profile.update'), [
            'first_name' => $this->student->first_name,
            'last_name' => $this->student->last_name,
            'profile_photo' => UploadedFile::fake()
                ->createWithContent('avatar.png', $png."<?php echo 'safe'; ?>")
                ->mimeType('image/png'),
        ]);

        $response->assertSessionHasErrors([
            'profile_photo' => self::SUSPICIOUS_MESSAGE,
        ]);

        $this->assertSame('profile-photos/current.png', $this->student->fresh()->profile_photo_path);
        $this->assertSame(['profile-photos/current.png'], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_assignment_submission_upload_rejects_unsafe_content_without_creating_a_submission(): void
    {
        $response = $this->actingAs($this->student)->post(
            route('student.courses.assignments.submit.store', [$this->course, $this->assignment]),
            ['files' => [$this->unsafeFile('notes.pdf')]]
        );

        $response->assertSessionHasErrors([
            'files.0' => self::SUSPICIOUS_MESSAGE,
        ]);

        $this->assertDatabaseCount('assignment_submissions', 0);
        $this->assertDatabaseCount('submission_files', 0);
        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_backup_restore_upload_rejects_unsafe_content_without_storing_the_backup(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.backup.restore'), [
            'backup_file' => $this->unsafeFile(
                'restore.sql',
                'application/sql',
                "-- harmless backup header\n<?php echo 'safe'; ?>\n"
            ),
        ]);

        $response->assertSessionHasErrors([
            'backup_file' => self::SUSPICIOUS_MESSAGE,
        ]);

        $this->assertSame([], File::glob(storage_path('app/backups/restore_*.sql')));
        $this->assertDatabaseCount('media_files', 0);
    }

    public function test_lesson_material_upload_rejects_unsafe_content_without_storing_the_material(): void
    {
        $response = $this->actingAs($this->instructor)->post(
            route('instructor.courses.modules.lessons.materials.upload', [
                $this->course,
                $this->module,
                $this->lesson,
            ]),
            ['files' => [$this->unsafeFile('lesson.pdf')]]
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(
            [self::SUSPICIOUS_MESSAGE],
            $response->json('errors')['file']
        );
        $this->assertDatabaseCount('lesson_materials', 0);
        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_module_attachment_upload_rejects_unsafe_content_without_storing_the_attachment(): void
    {
        $response = $this->actingAs($this->instructor)->post(
            route('instructor.courses.modules.attachments.upload', [$this->course, $this->module]),
            ['file' => $this->unsafeFile('module.pdf')]
        );

        $response->assertSessionHasErrors([
            'file' => self::SUSPICIOUS_MESSAGE,
        ]);

        $this->assertDatabaseCount('module_attachments', 0);
        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_api_file_upload_rejects_unsafe_content_without_creating_a_media_record(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/files/upload', [
            'file' => $this->unsafeFile('api-upload.pdf'),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed');

        $this->assertSame(
            [self::SUSPICIOUS_MESSAGE],
            $response->json('errors')['file']
        );
        $this->assertDatabaseCount('media_files', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    private function unsafeFile(
        string $name,
        string $mime = 'application/pdf',
        string $content = "%PDF-1.4\n<?php echo 'safe'; ?>\n"
    ): UploadedFile {
        return UploadedFile::fake()
            ->createWithContent($name, $content)
            ->mimeType($mime);
    }
}
