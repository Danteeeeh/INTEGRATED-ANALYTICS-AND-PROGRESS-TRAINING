<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Services\AaSyncService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AA is authoritative for enrollment: the LMS must not offer manual
 * enrollment paths (student self-enroll, admin CRUD, instructor CRUD).
 * Enrollment arrives ONLY via "Sync from AA".
 */
class EnrollmentAuthoritativeAaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $instructor;

    protected User $student;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->admin()->create(['status' => 'active']);
        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->student = User::factory()->student()->create(['status' => 'active']);

        $course = Course::factory()->create(['status' => 'published', 'created_by' => $this->instructor->id]);
        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);
    }

    public function test_students_cannot_self_enroll_route_is_gone(): void
    {
        // The self-enroll route was removed, so the URL no longer exists.
        $this->actingAs($this->student)
            ->post("/student/classes/{$this->class->id}/enroll")
            ->assertStatus(404);

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
        ]);
    }

    public function test_admin_cannot_create_enrollments_route_is_gone(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/enrollments', [
                'student_id' => $this->student->id,
                'class_id' => $this->class->id,
                'status' => 'active',
            ])
            ->assertStatus(405);

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
        ]);
    }

    public function test_instructor_cannot_create_enrollments_route_is_gone(): void
    {
        $this->actingAs($this->instructor)
            ->post('/instructor/enrollments', [
                'student_id' => $this->student->id,
                'class_id' => $this->class->id,
                'status' => 'active',
            ])
            ->assertStatus(405);
    }

    public function test_admin_can_still_view_enrollment_records(): void
    {
        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($this->admin)->get('/admin/enrollments')->assertOk();
        $this->actingAs($this->instructor)->get('/instructor/enrollments')->assertOk();
        $this->actingAs($this->student)->get('/student/enrollments')->assertOk();
    }

    /**
     * Regression: creating an LMS account for a student must not create
     * enrollment records. Enrollment is authoritative in AA and arrives only
     * through "Sync from AA" (AaSyncService).
     */
    public function test_creating_a_student_does_not_create_enrollments(): void
    {
        $section = Section::create(['name' => 'BSIT 1A', 'code' => 'BSIT-1A']);
        $this->class->update(['section_id' => $section->id, 'status' => 'active']);

        $this->actingAs($this->admin)
            ->post('/admin/students', [
                'first_name' => 'Newly',
                'last_name' => 'Created',
                'email' => 'newly.created@lms.local',
                'role_id' => Role::where('slug', Role::STUDENT)->value('id'),
                'section_id' => $section->id,
                'status' => 'active',
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('admin.students.index'));

        $created = User::where('email', 'newly.created@lms.local')->first();

        $this->assertNotNull($created, 'The admin must still be able to create the LMS account.');
        $this->assertDatabaseMissing('enrollments', ['student_id' => $created->id]);
    }

    /**
     * Regression: moving a student to another section must not enroll them in
     * that section's classes either.
     */
    public function test_changing_a_students_section_does_not_create_enrollments(): void
    {
        $section = Section::create(['name' => 'BSIT 1B', 'code' => 'BSIT-1B']);
        $this->class->update(['section_id' => $section->id, 'status' => 'active']);

        $this->actingAs($this->admin)
            ->put('/admin/students/'.$this->student->id, [
                'first_name' => $this->student->first_name,
                'last_name' => $this->student->last_name,
                'email' => $this->student->email,
                'role_id' => Role::where('slug', Role::STUDENT)->value('id'),
                'section_id' => $section->id,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseMissing('enrollments', ['student_id' => $this->student->id]);
    }

    public function test_only_the_sync_service_creates_enrollments(): void
    {
        // The only sanctioned writer: AaSyncService (via "Sync from AA").
        $service = app(AaSyncService::class);

        $reflection = new \ReflectionMethod($service, 'syncEnrollments');
        $this->assertTrue($reflection->isProtected());

        // No controller exposes enrollment creation anymore.
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Admin\EnrollmentController::class, 'store'),
            'Admin EnrollmentController must not expose store().'
        );
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Instructor\EnrollmentController::class, 'store'),
            'Instructor EnrollmentController must not expose store().'
        );
    }
}
