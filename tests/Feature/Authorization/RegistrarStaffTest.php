<?php

namespace Tests\Feature\Authorization;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarStaffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    protected function makeRegistrar(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::REGISTRAR)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    protected function makeInstructor(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    protected function makeStudent(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    protected function makeClassWithEnrollment(array $options = []): array
    {
        $instructor = $options['instructor'] ?? $this->makeInstructor();
        $student = $options['student'] ?? $this->makeStudent();
        $course = Course::factory()->create(['created_by' => $instructor->id]);
        $period = AcademicPeriod::factory()->create(['is_current' => true]);

        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'academic_period_id' => $period->id,
            'instructor_id' => $instructor->id,
            'schedule' => 'Mon/Wed 8:00-9:30 AM',
            'room' => 'Room 101',
            'status' => 'active',
        ]);

        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        return compact('instructor', 'student', 'course', 'period', 'class', 'enrollment');
    }

    public function test_registrar_can_view_schedules(): void
    {
        $this->makeClassWithEnrollment();

        $this->actingAs($this->makeRegistrar())
            ->get(route('registrar.schedules.index'))
            ->assertStatus(200)
            ->assertSee('Mon/Wed 8:00-9:30 AM');
    }

    public function test_registrar_can_view_grade_status(): void
    {
        $this->makeClassWithEnrollment();

        $this->actingAs($this->makeRegistrar())
            ->get(route('registrar.grades.index'))
            ->assertStatus(200);

        $this->actingAs($this->makeRegistrar())
            ->get(route('registrar.attendance.index'))
            ->assertStatus(200);
    }

    public function test_registrar_can_transfer_enrollment_to_another_class(): void
    {
        $data = $this->makeClassWithEnrollment();
        $secondClass = ClassModel::factory()->create([
            'course_id' => $data['course']->id,
            'academic_period_id' => $data['period']->id,
            'instructor_id' => $data['instructor']->id,
            'schedule' => 'Tue/Thu 1:00-2:30 PM',
            'status' => 'active',
        ]);

        $this->actingAs($this->makeRegistrar())
            ->post(route('registrar.enrollments.transfer', $data['enrollment']), [
                'class_id' => $secondClass->id,
            ])
            ->assertRedirect();

        $data['enrollment']->refresh();
        $this->assertSame($secondClass->id, $data['enrollment']->class_id);
        $this->assertSame('active', $data['enrollment']->status);
    }

    public function test_registrar_can_drop_and_reactivate_enrollment(): void
    {
        $data = $this->makeClassWithEnrollment();

        $this->actingAs($this->makeRegistrar())
            ->post(route('registrar.enrollments.deactivate', $data['enrollment']))
            ->assertRedirect();

        $this->assertSame('dropped', $data['enrollment']->fresh()->status);

        $this->actingAs($this->makeRegistrar())
            ->post(route('registrar.enrollments.activate', $data['enrollment']))
            ->assertRedirect();

        $this->assertSame('active', $data['enrollment']->fresh()->status);
    }

    public function test_registrar_can_return_grades_for_correction(): void
    {
        $data = $this->makeClassWithEnrollment();
        $data['enrollment']->update(['final_grade' => 88.5]);

        $this->actingAs($this->makeRegistrar())
            ->post(route('registrar.grades.return', $data['class']))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertNull($data['enrollment']->fresh()->final_grade);
    }

    public function test_registrar_can_view_attendance_report(): void
    {
        $data = $this->makeClassWithEnrollment();

        AttendanceRecord::create([
            'class_id' => $data['class']->id,
            'student_id' => $data['student']->id,
            'attendance_date' => now(),
            'session_title' => 'Week 1',
            'status' => 'present',
            'recorded_by' => $data['instructor']->id,
        ]);

        $this->actingAs($this->makeRegistrar())
            ->get(route('registrar.attendance.report', $data['class']))
            ->assertStatus(200)
            ->assertSee('Summary');
    }

    public function test_instructor_cannot_access_registrar_management_routes(): void
    {
        $data = $this->makeClassWithEnrollment();

        $instructor = $data['instructor'];

        $this->actingAs($instructor)
            ->get(route('registrar.schedules.index'))
            ->assertStatus(403);

        $this->actingAs($instructor)
            ->get(route('registrar.grades.index'))
            ->assertStatus(403);

        $this->actingAs($instructor)
            ->post(route('registrar.enrollments.transfer', $data['enrollment']), ['class_id' => $data['class']->id])
            ->assertStatus(403);
    }

    public function test_student_cannot_access_registrar_routes(): void
    {
        $data = $this->makeClassWithEnrollment();

        $this->actingAs($data['student'])
            ->get(route('registrar.schedules.index'))
            ->assertStatus(403);

        $this->actingAs($data['student'])
            ->get(route('registrar.attendance.index'))
            ->assertStatus(403);
    }
}
