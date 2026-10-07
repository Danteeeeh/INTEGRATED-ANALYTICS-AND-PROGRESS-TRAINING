<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Services\SubjectService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LMSSubjectsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $instructor;

    protected User $otherInstructor;

    protected User $student;

    protected Section $section;

    protected Course $course;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->admin()->create(['status' => 'active']);
        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->otherInstructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->student = User::factory()->student()->create(['status' => 'active']);

        $this->course = Course::factory()->create([
            'status' => 'published',
            'code' => 'IT-P3C',
            'title' => 'Data Privacy and Security',
            'credits' => 3,
        ]);

        $this->section = Section::create([
            'name' => 'BSIT 41010 IS',
            'code' => 'BSIT41010-IS',
        ]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'section_id' => $this->section->id,
            'code' => 'BSIT 41010 IS',
            'status' => 'active',
            'capacity' => 50,
        ]);

        $this->enroll($this->student, Enrollment::STATUS_ACTIVE, 88);
    }

    protected function enroll(User $student, string $status, ?float $score = null): Enrollment
    {
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'status' => $status,
            'enrolled_at' => now(),
        ]);

        if ($score !== null) {
            $item = GradeItem::firstOrCreate(
                ['class_id' => $this->class->id, 'title' => 'Week 1'],
                [
                    'item_type' => 'assignment',
                    'max_points' => 100,
                    'factor' => 1,
                    'is_released' => true,
                    'released_at' => now(),
                    'position' => 0,
                ]
            );

            Grade::create([
                'grade_item_id' => $item->id,
                'student_id' => $student->id,
                'points' => $score,
                'score_percent' => $score,
            ]);
        }

        return $enrollment;
    }

    // ── Route + rendering ────────────────────────────────────────

    public function test_subjects_page_renders_for_every_role(): void
    {
        $this->actingAs($this->admin)->get(route('admin.subjects.index'))->assertOk();
        $this->actingAs($this->instructor)->get(route('instructor.subjects.index'))->assertOk();
        $this->actingAs($this->student)->get(route('student.subjects.index'))->assertOk();
    }

    public function test_table_shows_the_expected_columns(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertSee('Section Code', false)
            ->assertSee('Students Count', false)
            ->assertSee('Subject Status', false)
            ->assertSee('Shortname', false)
            ->assertSee('Enrollment Status', false)
            ->assertSee('Subject Name', false)
            ->assertSee('Check Available Scores', false);
    }

    // ── Role scoping ─────────────────────────────────────────────

    public function test_admin_sees_every_subject(): void
    {
        $classes = app(SubjectService::class)->paginateForUser($this->admin);
        $this->assertSame(1, $classes->total());
    }

    public function test_instructor_only_sees_their_own_subjects(): void
    {
        $other = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->otherInstructor->id,
            'code' => 'OTHER-1',
            'status' => 'active',
        ]);

        $mine = app(SubjectService::class)->paginateForUser($this->instructor);
        $theirs = app(SubjectService::class)->paginateForUser($this->otherInstructor);

        $this->assertSame(1, $mine->total());
        $this->assertSame($this->class->id, $mine->first()->id);

        $this->assertSame(1, $theirs->total());
        $this->assertSame($other->id, $theirs->first()->id, 'Must not leak another instructor’s subject.');
    }

    public function test_student_only_sees_enrolled_subjects(): void
    {
        ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'code' => 'NOT-MINE',
            'status' => 'active',
        ]);

        $subjects = app(SubjectService::class)->paginateForUser($this->student);

        $this->assertSame(1, $subjects->total());
        $this->assertSame($this->class->id, $subjects->first()->id);
    }

    public function test_student_does_not_see_a_dropped_subject(): void
    {
        $this->student->enrollments()->update(['status' => Enrollment::STATUS_DROPPED]);

        $this->assertSame(
            0,
            app(SubjectService::class)->paginateForUser($this->student)->total(),
            'A dropped enrollment is not a subject the student is taking.'
        );
    }

    public function test_student_count_excludes_dropped_enrollments(): void
    {
        $dropped = User::factory()->student()->create(['status' => 'active']);
        $this->enroll($dropped, Enrollment::STATUS_DROPPED);

        $row = app(SubjectService::class)->paginateForUser($this->admin)->first();

        $this->assertSame(1, $row->enrolled_count, 'Headcount must not include dropped students.');
    }

    // ── Filters ──────────────────────────────────────────────────

    public function test_search_matches_section_code_and_subject_name(): void
    {
        $service = app(SubjectService::class);

        $this->assertSame(1, $service->paginateForUser($this->admin, ['search' => '41010'])->total());
        $this->assertSame(1, $service->paginateForUser($this->admin, ['search' => 'Privacy'])->total());
        $this->assertSame(1, $service->paginateForUser($this->admin, ['search' => 'IT-P3C'])->total());
        $this->assertSame(0, $service->paginateForUser($this->admin, ['search' => 'zzz'])->total());
    }

    public function test_subject_status_filter_works(): void
    {
        $service = app(SubjectService::class);

        $this->assertSame(1, $service->paginateForUser($this->admin, ['subject_status' => 'draft'])->total());
        $this->assertSame(0, $service->paginateForUser($this->admin, ['subject_status' => 'verified'])->total());

        $this->class->markSubjectVerified($this->admin->id);

        $this->assertSame(0, $service->paginateForUser($this->admin, ['subject_status' => 'draft'])->total());
        $this->assertSame(1, $service->paginateForUser($this->admin, ['subject_status' => 'verified'])->total());
    }

    // ── Check Available Scores ───────────────────────────────────

    public function test_score_availability_reports_complete_when_all_graded(): void
    {
        $report = app(SubjectService::class)->scoreAvailability($this->class);

        $this->assertTrue($report['is_complete']);
        $this->assertSame([], $report['blockers']);
        $this->assertSame(1, $report['enrollment_count']);
        $this->assertSame(1, $report['graded_count']);
        $this->assertSame(0, $report['missing_count']);
        $this->assertEqualsWithDelta(88.0, $report['class_average'], 0.01);
    }

    public function test_score_availability_flags_an_ungraded_student(): void
    {
        $pending = User::factory()->student()->create(['status' => 'active']);
        $this->enroll($pending, Enrollment::STATUS_ACTIVE);

        $report = app(SubjectService::class)->scoreAvailability($this->class);

        $this->assertFalse($report['is_complete']);
        $this->assertSame(1, $report['missing_count']);
        $this->assertContains($pending->id, $report['missing_student_ids']);
        $this->assertNotEmpty($report['blockers']);
    }

    public function test_score_availability_flags_unreleased_items(): void
    {
        GradeItem::create([
            'class_id' => $this->class->id,
            'title' => 'Final Exam',
            'item_type' => 'exam',
            'max_points' => 50,
            'factor' => 1,
            'is_released' => false,
            'position' => 1,
        ]);

        $report = app(SubjectService::class)->scoreAvailability($this->class);

        $this->assertFalse($report['is_complete']);
        $this->assertSame(1, $report['unreleased_count']);
    }

    public function test_unreleased_items_do_not_count_toward_the_grade(): void
    {
        // Matches the canonical rule everywhere else: released + graded only.
        GradeItem::create([
            'class_id' => $this->class->id,
            'title' => 'Unreleased',
            'item_type' => 'exam',
            'max_points' => 100,
            'factor' => 1,
            'is_released' => false,
            'position' => 1,
        ]);

        $report = app(SubjectService::class)->scoreAvailability($this->class);

        $this->assertEqualsWithDelta(88.0, $report['class_average'], 0.01);
    }

    // ── Verification lifecycle ───────────────────────────────────

    public function test_admin_can_verify_a_complete_subject(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.verify', $this->class))
            ->assertRedirect();

        $this->class->refresh();

        $this->assertTrue($this->class->isSubjectVerified());
        $this->assertSame($this->admin->id, $this->class->subject_verified_by);
        $this->assertNotNull($this->class->subject_verified_at);
    }

    public function test_verification_is_refused_while_grading_is_incomplete(): void
    {
        $pending = User::factory()->student()->create(['status' => 'active']);
        $this->enroll($pending, Enrollment::STATUS_ACTIVE);

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.verify', $this->class))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($this->class->refresh()->isSubjectVerified());
    }

    public function test_instructor_can_verify_their_own_subject(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.subjects.verify', $this->class))
            ->assertRedirect();

        $this->assertTrue($this->class->refresh()->isSubjectVerified());
    }

    public function test_instructor_cannot_verify_someone_elses_subject(): void
    {
        $this->actingAs($this->otherInstructor)
            ->post(route('instructor.subjects.verify', $this->class))
            ->assertForbidden();

        $this->assertFalse($this->class->refresh()->isSubjectVerified());
    }

    public function test_student_cannot_verify_or_unverify(): void
    {
        // The student panel has no verify route at all, so this must 404 rather
        // than quietly succeed.
        $this->actingAs($this->student)
            ->post('/student/subjects/'.$this->class->id.'/verify')
            ->assertNotFound();

        $this->assertFalse($this->class->refresh()->isSubjectVerified());
    }

    public function test_only_admin_can_reopen_a_verified_subject(): void
    {
        $this->class->markSubjectVerified($this->admin->id);

        // Instructors have no unverify route; reopening is an admin-only action.
        $this->actingAs($this->instructor)
            ->post('/instructor/subjects/'.$this->class->id.'/unverify')
            ->assertNotFound();

        $this->assertTrue($this->class->refresh()->isSubjectVerified());

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.unverify', $this->class))
            ->assertRedirect();

        $this->assertFalse($this->class->refresh()->isSubjectVerified());
    }

    public function test_student_cannot_open_the_scores_page_of_another_subject(): void
    {
        $other = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'code' => 'NOT-MINE',
            'status' => 'active',
        ]);

        $this->actingAs($this->student)
            ->get(route('student.subjects.scores', $other))
            ->assertForbidden();
    }

    public function test_student_can_open_the_scores_page_of_their_own_subject(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.subjects.scores', $this->class))
            ->assertOk()
            ->assertSee('Check Available Scores', false);
    }
}