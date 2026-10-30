<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentExtension;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Student → course → assignment → submit.
 *
 * The due date is final: once it passes, no new submission is accepted, even
 * when the assignment still carries the legacy `allow_late` flag. A per-student
 * extension is the only way back in. Separately, max_attempts = 1 (the schema
 * default) must not block resubmission when the instructor enabled it.
 */
class AssignmentSubmissionRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected Course $course;

    protected ClassModel $class;

    protected Assignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => 'active',
        ]);

        $this->assignment = Assignment::create([
            'class_id' => $this->class->id,
            'created_by' => $this->instructor->id,
            'title' => 'Reflection Paper',
            'slug' => 'reflection-'.uniqid(),
            'instructions' => 'Write a short reflection.',
            'points' => 100,
            'submission_type' => Assignment::TYPE_TEXT,
            'due_date' => now()->addDay(),
            'allow_late' => true,
            'status' => Assignment::STATUS_PUBLISHED,
        ]);
    }

    private function submitUrl(): string
    {
        return route('student.courses.assignments.submit.store', [$this->course, $this->assignment]);
    }

    private function formUrl(): string
    {
        return route('student.courses.assignments.submit', [$this->course, $this->assignment]);
    }

    public function test_on_time_submission_is_accepted(): void
    {
        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['submission_text' => 'My answer'])
            ->assertRedirect();

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'is_late' => false,
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ]);
    }

    public function test_overdue_submission_is_refused_even_when_the_legacy_late_flag_is_on(): void
    {
        $this->assignment->update([
            'due_date' => now()->subDay(),
            'allow_late' => true,
        ]);

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['submission_text' => 'Too late'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('assignment_submissions', 0);
    }

    public function test_overdue_submission_is_refused_when_the_legacy_late_flag_is_off(): void
    {
        $this->assignment->update([
            'due_date' => now()->subDay(),
            'allow_late' => false,
        ]);

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['submission_text' => 'Too late'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('assignment_submissions', 0);
    }

    public function test_submit_form_is_closed_once_overdue(): void
    {
        $this->assignment->update([
            'due_date' => now()->subDay(),
            'allow_late' => true,
        ]);

        $this->actingAs($this->student)
            ->get($this->formUrl())
            ->assertRedirect(route('student.courses.assignments.show', [$this->course, $this->assignment]));
    }

    public function test_a_granted_extension_reopens_an_overdue_assignment(): void
    {
        $this->assignment->update([
            'due_date' => now()->subDay(),
            'allow_late' => false,
        ]);

        AssignmentExtension::create([
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'extended_until' => now()->addDays(2),
            'reason' => 'Medical leave',
            'granted_by' => $this->instructor->id,
        ]);

        $this->actingAs($this->student)
            ->get($this->formUrl())
            ->assertOk();

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['submission_text' => 'With an extension'])
            ->assertRedirect();

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_resubmission_works_when_allowed_even_though_max_attempts_defaults_to_one(): void
    {
        $this->assignment->update(['allow_resubmission' => true]);

        AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'submission_text' => 'First attempt',
            'submitted_at' => now(),
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ]);

        $this->actingAs($this->student)
            ->post($this->submitUrl(), ['submission_text' => 'Second attempt'])
            ->assertRedirect();

        $this->assertSame(
            2,
            AssignmentSubmission::where('assignment_id', $this->assignment->id)
                ->where('student_id', $this->student->id)
                ->count()
        );

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'attempt_number' => 2,
        ]);
    }
}
