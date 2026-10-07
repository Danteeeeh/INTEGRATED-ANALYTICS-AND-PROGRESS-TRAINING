<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Role;
use App\Models\User;
use App\Services\ExamService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §Exams — the instructor create form never sent exam_type.
 *
 * Instructor\ExamController::store() had no exam_type validation rule, so the
 * key was missing from the payload; ExamService::createExam() then indexed
 * $data['exam_type'] while building its audit row and raised
 * "Undefined array key \"exam_type\"", aborting the whole transaction.
 */
class ExamTypePayloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $instructor;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);
    }

    /** Minimal payload exactly as the instructor form submitted it before the fix. */
    private function instructorPayload(array $overrides = []): array
    {
        return array_merge([
            'class_id' => $this->class->id,
            'title' => 'Midterm',
            'description' => null,
            'instructions' => null,
            'duration_minutes' => 60,
            'result_visibility' => 'after_grading',
            'status' => 'draft',
        ], $overrides);
    }

    public function test_service_tolerates_a_payload_without_exam_type(): void
    {
        // This is the exact crash: no exam_type key at all.
        $exam = app(ExamService::class)->createExam($this->instructorPayload());

        $this->assertInstanceOf(Exam::class, $exam);
        $this->assertSame(
            Exam::TYPE_PRELIM,
            $exam->exam_type,
            'createExam should apply the first semestral exam as its default.'
        );
    }

    public function test_service_tolerates_a_payload_without_course_id(): void
    {
        $exam = app(ExamService::class)->createExam($this->instructorPayload());

        $this->assertNull($exam->course_id);
    }

    public function test_service_records_the_persisted_type_in_the_audit_log(): void
    {
        $exam = app(ExamService::class)->createExam($this->instructorPayload(['exam_type' => 'finals']));

        $audit = \App\Models\AuditLog::where('resource_type', Exam::class)
            ->where('resource_id', $exam->id)
            ->firstOrFail();

        $this->assertSame('finals', $audit->new_values['exam_type']);
    }

    public function test_instructor_can_create_an_exam(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.exams.store'), $this->instructorPayload(['exam_type' => 'midterm']))
            ->assertRedirect();

        $this->assertDatabaseHas('exams', [
            'class_id' => $this->class->id,
            'title' => 'Midterm',
            'exam_type' => 'midterm',
        ]);
    }

    public function test_instructor_form_requires_exam_type(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.exams.store'), $this->instructorPayload())
            ->assertSessionHasErrors('exam_type');

        $this->assertDatabaseMissing('exams', ['title' => 'Midterm']);
    }

    public function test_instructor_create_and_edit_forms_offer_the_type_field(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.exams.create'))
            ->assertOk()
            ->assertSee('name="exam_type"', false);

        $exam = Exam::create([
            'title' => 'Existing',
            'slug' => 'existing-'.uniqid(),
            'exam_type' => 'finals',
            'class_id' => $this->class->id,
            'course_id' => $this->class->course_id,
            'duration_minutes' => 60,
            'result_visibility' => 'after_grading',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.exams.edit', [$this->class->course, $exam]))
            ->assertOk()
            ->assertSee('name="exam_type"', false);
    }

    public function test_instructor_can_update_the_type(): void
    {
        $exam = Exam::create([
            'title' => 'Existing',
            'slug' => 'existing-'.uniqid(),
            'exam_type' => 'midterm',
            'class_id' => $this->class->id,
            'course_id' => $this->class->course_id,
            'duration_minutes' => 60,
            'result_visibility' => 'after_grading',
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.courses.exams.update', [$this->class->course, $exam]),
                $this->instructorPayload(['exam_type' => 'finals']))
            ->assertRedirect();

        $this->assertSame('finals', $exam->fresh()->exam_type);
    }

    // ── The admin side-nav page the report also mentions ─────────

    public function test_admin_exams_index_renders(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.exams.index'))
            ->assertOk();
    }

    public function test_admin_exams_index_filters_by_type(): void
    {
        Exam::create([
            'title' => 'A midterm', 'slug' => 'a-'.uniqid(), 'exam_type' => 'midterm',
            'class_id' => $this->class->id, 'course_id' => $this->class->course_id,
            'duration_minutes' => 60, 'result_visibility' => 'after_grading',
            'status' => 'draft', 'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.exams.index', ['exam_type' => 'finals']))
            ->assertOk()
            ->assertDontSee('A midterm');
    }
}