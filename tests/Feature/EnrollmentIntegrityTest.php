<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentService;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

class EnrollmentIntegrityTest extends TestCase
{
    use CreatesLmsUsers;

    public function test_unlimited_class_capacity_accepts_student_enrollment(): void
    {
        $student = $this->makeUser('student');
        $instructor = $this->makeUser('instructor');
        $class = ClassModel::factory()->create([
            'course_id' => Course::factory()->create(['created_by' => $instructor->id])->id,
            'instructor_id' => $instructor->id,
            'capacity' => null,
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->post(route('student.classes.enroll', $class))
            ->assertRedirect(route('student.classes.show', $class));

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);
        $this->assertNull($class->max_students);
        $this->assertFalse($class->isFull());
    }

    public function test_enrollment_service_supports_partial_updates_without_status_key(): void
    {
        $student = $this->makeUser('student');
        $instructor = $this->makeUser('instructor');
        $class = ClassModel::factory()->create([
            'course_id' => Course::factory()->create(['created_by' => $instructor->id])->id,
            'instructor_id' => $instructor->id,
            'capacity' => 30,
            'status' => 'active',
        ]);
        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'completed_at' => null,
        ]);

        $updated = app(EnrollmentService::class)->updateEnrollment($enrollment->id, [
            'notes' => 'Registrar note',
        ]);

        $this->assertSame('active', $updated->status);
        $this->assertSame('Registrar note', $updated->notes);
        $this->assertNull($updated->completed_at);
    }

    public function test_enrollment_request_statuses_match_database_enum(): void
    {
        $request = new \App\Http\Requests\StoreEnrollmentRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('pending,active,completed,dropped', $rules['status']);
        $this->assertStringNotContainsString('suspended', $rules['status']);

        $request = new \App\Http\Requests\UpdateEnrollmentRequest();
        $rules = $request->rules();

        $this->assertStringContainsString('pending,active,completed,dropped', $rules['status']);
        $this->assertStringNotContainsString('suspended', $rules['status']);
    }
}
