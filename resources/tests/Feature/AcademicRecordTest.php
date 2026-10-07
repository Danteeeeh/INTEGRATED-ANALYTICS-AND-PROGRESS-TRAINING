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
use App\Services\GradeService;
use App\Services\SubjectService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicRecordTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected User $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->admin()->create([
            'status' => 'active',
            'identifier' => 'STU-0001',
        ]);

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);

        $program = \App\Models\Program::create(['name' => 'BSIT', 'code' => 'BSIT']);

        $this->student = User::factory()->student()->create([
            'status' => 'active',
            'identifier' => 'STU-0042',
            'first_name' => 'Juan',
            'last_name' => 'Pedro',
            'program_id' => $program->id,
        ]);
    }

    /**
     * @param  array{credits?: int, score?: float, released?: bool}  $options
     */
    protected function subject(string $code, string $title, array $options = []): ClassModel
    {
        $course = Course::factory()->create([
            'status' => 'published',
            'code' => $code,
            'title' => $title,
            'credits' => $options['credits'] ?? 3,
        ]);

        $section = Section::create(['name' => 'Section '.$code, 'code' => 'SEC-'.$code]);

        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'section_id' => $section->id,
            'code' => 'BSIT '.$code,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $class->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        if (isset($options['score'])) {
            $item = GradeItem::create([
                'class_id' => $class->id,
                'title' => 'Final',
                'item_type' => 'exam',
                'max_points' => 100,
                'factor' => 1,
                'is_released' => $options['released'] ?? true,
                'released_at' => now(),
                'position' => 0,
            ]);

            Grade::create([
                'grade_item_id' => $item->id,
                'student_id' => $this->student->id,
                'points' => $options['score'],
                'score_percent' => $options['score'],
            ]);
        }

        return $class;
    }

    // ── Service ─────────────────────────────────────────────────

    public function test_gwa_is_weighted_by_credits(): void
    {
        // 3 credits at 90 and 5 credits at 80 → (90*3 + 80*5) / 8 = 83.75
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);
        $this->subject('IT3B', 'Networking', ['credits' => 5, 'score' => 80]);

        $record = app(SubjectService::class)->academicRecord($this->student);

        $this->assertCount(2, $record['lines']);
        $this->assertEqualsWithDelta(83.75, $record['gwa'], 0.01);
        $this->assertEqualsWithDelta(8.0, $record['units_earned'], 0.01);
    }

    public function test_a_plain_average_would_be_wrong(): void
    {
        // Guards the weighting: the naive mean is 85.00, the credit-weighted GWA
        // is 83.75. If this ever equals 85 the weighting was lost.
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);
        $this->subject('IT3B', 'Networking', ['credits' => 5, 'score' => 80]);

        $record = app(SubjectService::class)->academicRecord($this->student);

        $this->assertNotEqualsWithDelta(85.0, (float) $record['gwa'], 0.01);
    }

    public function test_ungraded_subjects_are_excluded_from_the_gwa(): void
    {
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);
        $this->subject('IT3B', 'Networking', ['credits' => 3]);

        $record = app(SubjectService::class)->academicRecord($this->student);

        $this->assertSame(2, $record['total_count']);
        $this->assertSame(1, $record['graded_count']);
        $this->assertEqualsWithDelta(90.0, $record['gwa'], 0.01, 'Only graded subjects count.');
    }

    public function test_dropped_subjects_are_not_on_the_record(): void
    {
        $class = $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);

        $this->student->enrollments()->update(['status' => Enrollment::STATUS_DROPPED]);

        $record = app(SubjectService::class)->academicRecord($this->student);

        $this->assertSame(0, $record['total_count']);
        $this->assertNull($record['gwa']);
    }

    public function test_unreleased_items_produce_no_grade(): void
    {
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90, 'released' => false]);

        $record = app(SubjectService::class)->academicRecord($this->student);

        $this->assertSame(1, $record['total_count']);
        $this->assertSame(0, $record['graded_count']);
        $this->assertNull($record['gwa']);
        $this->assertSame('No grades on record', $record['overall_remark']);
    }

    public function test_record_marks_verified_and_unverified_subjects(): void
    {
        $verified = $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);
        $this->subject('IT3B', 'Networking', ['credits' => 3, 'score' => 85]);

        $verified->markSubjectVerified($this->admin->id);

        $record = app(SubjectService::class)->academicRecord($this->student);

        $this->assertSame(2, $record['total_count']);
        $this->assertSame(1, $record['unverified_count']);
        $this->assertSame('Passed', $record['overall_remark']);
    }

    public function test_record_matches_the_gradebook_grade(): void
    {
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 87]);

        $service = app(SubjectService::class);
        $record = $service->academicRecord($this->student);
        $class = $record['lines'][0]['class_id'];

        $gradebook = $service->scoreAvailability(\App\Models\ClassModel::find($class));

        // Same computation, so the transcript can never disagree with the gradebook.
        $this->assertEqualsWithDelta(
            $gradebook['summaries'][$this->student->id]['percent'],
            (float) $record['lines'][0]['final_grade'],
            0.001
        );
    }

    public function test_below_passing_grade_is_reported(): void
    {
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 55]);

        $record = app(SubjectService::class)->academicRecord($this->student);

        $this->assertSame('Below passing grade', $record['overall_remark']);
    }

    // ── Controller / views ──────────────────────────────────────

    public function test_admin_can_open_the_record_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.academic-records.index'))
            ->assertOk()
            ->assertSee('Produce an Academic Record', false);
    }

    public function test_admin_can_produce_a_record(): void
    {
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);

        $this->actingAs($this->admin)
            ->get(route('admin.academic-records.show', $this->student))
            ->assertOk()
            ->assertSee('Academic Record', false)
            ->assertSee('STU-0042', false)
            ->assertSee('Juan', false)
            ->assertSee('Databases', false)
            ->assertSee('Registrar', false);
    }

    public function test_record_page_shows_the_gwa(): void
    {
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);

        $this->actingAs($this->admin)
            ->get(route('admin.academic-records.show', $this->student))
            ->assertOk()
            ->assertSee('General Weighted Average', false)
            ->assertSee('90.00', false);
    }

    public function test_csv_export_has_a_row_per_subject(): void
    {
        $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);
        $this->subject('IT3B', 'Networking', ['credits' => 5, 'score' => 80]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.academic-records.export', $this->student));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Student No', $csv);
        $this->assertStringContainsString('Databases', $csv);
        $this->assertStringContainsString('Networking', $csv);
        // header + 2 subjects + summary line
        $this->assertCount(4, array_filter(explode("\n", trim($csv))));
    }

    public function test_instructor_cannot_open_the_record_index(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('admin.academic-records.index'))
            ->assertForbidden();
    }

    public function test_student_cannot_open_the_record_index(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.academic-records.index'))
            ->assertForbidden();
    }

    public function test_record_can_be_scoped_to_one_period(): void
    {
        $period = \App\Models\AcademicPeriod::factory()->create(['name' => 'AY 2026-2027']);

        $scoped = $this->subject('IT3A', 'Databases', ['credits' => 3, 'score' => 90]);
        $scoped->update(['academic_period_id' => $period->id]);
        $this->subject('IT3B', 'Networking', ['credits' => 3, 'score' => 80]);

        $service = app(SubjectService::class);

        $this->assertSame(2, $service->academicRecord($this->student)['total_count']);
        $this->assertSame(1, $service->academicRecord($this->student, $period->id)['total_count']);
    }

    /**
     * Regression: computeSummariesForEnrollments() used `$a += $b`, which is an
     * array union. A student holding two of the passed enrollments therefore got
     * only the first class's summary and every other class was silently dropped.
     */
    public function test_multi_class_enrollment_summaries_are_merged_not_dropped(): void
    {
        $a = $this->subject('IT3A', 'Databases', ['score' => 80]);
        $b = $this->subject('IT3B', 'Networking', ['score' => 100]);

        $enrollments = Enrollment::where('student_id', $this->student->id)
            ->whereIn('class_id', [$a->id, $b->id])
            ->get();

        $merged = app(GradeService::class)->computeSummariesForEnrollments($enrollments);

        $this->assertArrayHasKey($this->student->id, $merged);
        $this->assertSame(2, $merged[$this->student->id]['graded_items'], 'Both classes must contribute.');
        $this->assertEqualsWithDelta(200.0, (float) $merged[$this->student->id]['max_points'], 0.01);
        $this->assertEqualsWithDelta(90.0, (float) $merged[$this->student->id]['percent'], 0.01);
    }

    public function test_per_class_summaries_stay_separate(): void
    {
        $a = $this->subject('IT3A', 'Databases', ['score' => 80]);
        $b = $this->subject('IT3B', 'Networking', ['score' => 100]);

        $enrollments = Enrollment::where('student_id', $this->student->id)
            ->whereIn('class_id', [$a->id, $b->id])
            ->get();

        $perClass = app(GradeService::class)->computeSummariesByClass($enrollments);

        $this->assertEqualsWithDelta(80.0, (float) $perClass[$a->id][$this->student->id]['percent'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $perClass[$b->id][$this->student->id]['percent'], 0.01);
    }
}