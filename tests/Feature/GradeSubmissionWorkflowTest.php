<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\GradeSubmission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Instructor grade extraction & submission workflow.
 *
 * Covers the master-prompt §4 flow (extract → preview → export → submit →
 * history) plus the §2/§3 monitoring surfaces, and pins the two hard rules:
 *  - one instructor can never touch another instructor's gradebook (IDOR), and
 *  - exporting/submitting never rewrites an official AA record.
 */
class GradeSubmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $otherInstructor;

    private User $student;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->otherInstructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->student = User::factory()->student()->create(['status' => 'active']);

        $course = Course::factory()->create([
            'code' => 'MATH101',
            'title' => 'Mathematics in the Modern World',
            'status' => 'published',
        ]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'code' => 'MATH101-A',
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);
    }

    private function releaseGrade(User $student, float $points): void
    {
        $item = GradeItem::create([
            'class_id' => $this->class->id,
            'title' => 'Midterm Quiz',
            'item_type' => 'quiz',
            'max_points' => 100,
            'factor' => 1,
            'is_released' => true,
            'released_at' => now()->subDay(),
            'position' => 1,
        ]);

        Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $student->id,
            'points' => $points,
            'score_percent' => $points,
            'letter_grade' => 'B',
            'graded_by' => $this->instructor->id,
            'graded_at' => now(),
        ]);
    }

    // ── Instructor: index, preview, export ───────────────────────────────

    public function test_instructor_can_open_the_extract_and_submit_page(): void
    {
        $response = $this->actingAs($this->instructor)->get('/instructor/grade-submissions');

        $response->assertOk();
        $response->assertSee('Grade Submission History');
        $response->assertSee('MATH101-A');
    }

    public function test_instructor_preview_extracts_lms_scores_for_own_class(): void
    {
        $this->releaseGrade($this->student, 85);

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.grade-submissions.preview', $this->class));

        $response->assertOk();
        $response->assertSee($this->student->name);
        $response->assertSee('85.00');
        $response->assertSee('Submit Grades');
    }

    public function test_missing_grades_are_flagged_in_the_preview(): void
    {
        // No grades released at all — the student's work is incomplete.
        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.grade-submissions.preview', $this->class));

        $response->assertOk();
        $response->assertSee('Missing');
        $response->assertSee('Missing / incomplete');
    }

    public function test_instructor_cannot_preview_another_instructors_class(): void
    {
        $this->actingAs($this->otherInstructor)
            ->get(route('instructor.grade-submissions.preview', $this->class))
            ->assertForbidden();
    }

    public function test_instructor_cannot_submit_another_instructors_class(): void
    {
        $this->actingAs($this->otherInstructor)
            ->post(route('instructor.grade-submissions.store', $this->class), [])
            ->assertForbidden();

        $this->assertDatabaseCount('grade_submissions', 0);
    }

    public function test_export_does_not_submit_and_omits_the_final_grade_column(): void
    {
        $this->releaseGrade($this->student, 90);

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.grade-submissions.export', $this->class));

        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Student ID', $content);
        $this->assertStringContainsString('LMS Score %', $content);
        $this->assertStringContainsString('MATH101-A', $content);
        // The LMS never publishes the official final grade.
        $this->assertStringNotContainsString('Final Grade', $content);

        // Exporting must not create a submission.
        $this->assertDatabaseCount('grade_submissions', 0);
    }

    // ── Instructor: submit + history ─────────────────────────────────────

    public function test_submitting_records_history_without_touching_official_records(): void
    {
        $this->releaseGrade($this->student, 75);

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.grade-submissions.store', $this->class), [
                'notes' => 'Midterm batch',
            ]);

        $submission = GradeSubmission::firstOrFail();

        $response->assertRedirect(route('instructor.grade-submissions.show', $submission));

        $this->assertSame($this->class->id, $submission->class_id);
        $this->assertSame($this->instructor->id, $submission->instructor_id);
        $this->assertSame(GradeSubmission::STATUS_SUBMITTED, $submission->status);
        $this->assertSame(1, $submission->student_count);
        $this->assertSame(1, $submission->graded_count);
        $this->assertSame(0, $submission->missing_count);
        $this->assertSame('Midterm batch', $submission->notes);

        // The official record is untouched: AA owns the final grade.
        $this->assertNull($this->class->enrollments()->first()->final_grade);
    }

    public function test_instructor_cannot_view_another_instructors_submission(): void
    {
        $this->releaseGrade($this->student, 70);

        $this->actingAs($this->instructor)
            ->post(route('instructor.grade-submissions.store', $this->class), []);

        $submission = GradeSubmission::firstOrFail();

        $this->actingAs($this->otherInstructor)
            ->get(route('instructor.grade-submissions.show', $submission))
            ->assertForbidden();
    }

    // ── Cross-role boundaries ────────────────────────────────────────────

    public function test_student_cannot_reach_instructor_grade_submission_routes(): void
    {
        $this->actingAs($this->student)
            ->get('/instructor/grade-submissions')
            ->assertForbidden();

        $this->actingAs($this->student)
            ->get(route('instructor.grade-submissions.preview', $this->class))
            ->assertForbidden();
    }

    public function test_admin_can_monitor_grade_submissions(): void
    {
        $this->releaseGrade($this->student, 88);

        $this->actingAs($this->instructor)
            ->post(route('instructor.grade-submissions.store', $this->class), []);

        $admin = User::factory()->admin()->create(['status' => 'active']);

        $index = $this->actingAs($admin)->get('/admin/grade-submissions');
        $index->assertOk();
        $index->assertSee('Grade Submission Monitoring');
        $index->assertSee('MATH101-A');

        $show = $this->actingAs($admin)
            ->get(route('admin.grade-submissions.show', GradeSubmission::firstOrFail()));
        $show->assertOk();
    }

    public function test_instructor_cannot_reach_admin_monitoring(): void
    {
        $this->actingAs($this->instructor)
            ->get('/admin/grade-submissions')
            ->assertForbidden();
    }

    public function test_aa_staff_can_view_submission_statuses_read_only(): void
    {
        $this->releaseGrade($this->student, 92);

        $this->actingAs($this->instructor)
            ->post(route('instructor.grade-submissions.store', $this->class), []);

        $aaStaff = User::factory()->aaStaff()->create(['status' => 'active']);

        $this->actingAs($aaStaff)
            ->get('/aa/grade-submissions')
            ->assertOk()
            ->assertSee('Read-Only');

        // No write path exists on the AA panel.
        $this->actingAs($aaStaff)->post('/aa/grade-submissions')->assertStatus(405);
    }

    public function test_guest_is_redirected_from_grade_submission_routes(): void
    {
        $this->get('/instructor/grade-submissions')->assertRedirect(route('login'));
        $this->get('/admin/grade-submissions')->assertRedirect(route('login'));
        $this->get('/aa/grade-submissions')->assertRedirect(route('login'));
    }
}
