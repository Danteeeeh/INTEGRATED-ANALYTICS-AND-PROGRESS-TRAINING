<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeConfiguration;
use App\Models\GradeItem;
use App\Models\Role;
use App\Models\User;
use App\Services\GradeBreakdownService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A student who had finished only some of the work was already shown a final
 * grade ("80.40% / B / PASSED") while the rest was still ungraded.
 *
 * A final grade must only be produced once every released item of every graded
 * component has a grade. Partial results stay visible as a provisional score,
 * but they must never be reported as the student's grade.
 *
 * Component averaging is the intended behaviour: every quiz is averaged
 * together, every assignment together, every exam together, and only then are
 * the component averages weighted.
 */
class GradeCompletenessTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(10),
        ]);
    }

    private function item(string $type, string $title, int $position, bool $released = true): GradeItem
    {
        return GradeItem::create([
            'class_id' => $this->class->id,
            'title' => $title,
            'item_type' => $type,
            'max_points' => 100,
            'factor' => 1,
            'is_released' => $released,
            'released_at' => $released ? now()->subDay() : null,
            'position' => $position,
        ]);
    }

    private function grade(GradeItem $item, float $points): Grade
    {
        return Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $this->student->id,
            'points' => $points,
            'score_percent' => $points,
            'graded_at' => now()->subDay(),
        ]);
    }

    private function configure(array $weights): GradeConfiguration
    {
        $attributes = ['class_id' => $this->class->id, 'created_by' => $this->instructor->id];

        foreach (GradeConfiguration::COMPONENTS as $type => $meta) {
            $attributes[$meta['column']] = $weights[$type] ?? 0;
        }

        return GradeConfiguration::create($attributes);
    }

    /** @return array<string, mixed> */
    private function summary(): array
    {
        $result = app(GradeBreakdownService::class)
            ->forClass($this->class, [$this->student->id]);

        return $result[$this->student->id];
    }

    public function test_partial_work_does_not_produce_a_final_grade(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 40, GradeItem::TYPE_EXAM => 60]);

        $quiz1 = $this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 1);
        $quiz2 = $this->item(GradeItem::TYPE_QUIZ, 'Quiz 2', 2);
        $exam = $this->item(GradeItem::TYPE_EXAM, 'Final Exam', 3);

        // Only the first quiz is done. Everything else is outstanding.
        $this->grade($quiz1, 90);

        $summary = $this->summary();

        $this->assertFalse(
            $summary['is_graded'],
            'A student with ungraded quizzes and an ungraded exam must not have a final grade.'
        );
        $this->assertFalse($summary['is_complete']);
        $this->assertSame(2, $summary['missing_items']);
        $this->assertSame(3, $summary['expected_items']);
    }

    public function test_complete_work_produces_a_final_grade(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 40, GradeItem::TYPE_EXAM => 60]);

        $quiz1 = $this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 1);
        $quiz2 = $this->item(GradeItem::TYPE_QUIZ, 'Quiz 2', 2);
        $exam = $this->item(GradeItem::TYPE_EXAM, 'Final Exam', 3);

        $this->grade($quiz1, 90);
        $this->grade($quiz2, 50);
        $this->grade($exam, 80);

        $summary = $this->summary();

        $this->assertTrue($summary['is_complete']);
        $this->assertTrue($summary['is_graded']);
        $this->assertSame(0, $summary['missing_items']);

        // Quiz average (90 + 50) / 2 = 70. Exams average = 80.
        // 70 * 0.4 + 80 * 0.6 = 76.
        $this->assertEqualsWithDelta(76.0, $summary['final_grade'], 0.01);
    }

    public function test_components_are_averaged_before_they_are_weighted(): void
    {
        // Three quizzes and one exam. Averaging each group first keeps the
        // single exam from being outweighed simply by existing once.
        $this->configure([GradeItem::TYPE_QUIZ => 75, GradeItem::TYPE_EXAM => 25]);

        foreach ([['Q1', 100], ['Q2', 100], ['Q3', 50]] as $i => [$title, $points]) {
            $this->grade($this->item(GradeItem::TYPE_QUIZ, $title, $i + 1), $points);
        }

        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 9), 90);

        $summary = $this->summary();

        $this->assertTrue($summary['is_graded']);

        // Quiz average = (100 + 100 + 50) / 3 = 83.33
        $this->assertEqualsWithDelta(83.33, $summary['components'][GradeItem::TYPE_QUIZ]['percent'], 0.01);

        // 83.33 * 0.75 + 90 * 0.25 = 85.0
        $this->assertEqualsWithDelta(85.0, $summary['final_grade'], 0.01);
    }

    public function test_unreleased_items_are_not_required(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 100]);

        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 1), 88);

        // Not released yet, so it must not block the final grade.
        $this->item(GradeItem::TYPE_QUIZ, 'Quiz 2 (hidden)', 2, released: false);

        $summary = $this->summary();

        $this->assertTrue($summary['is_complete'], 'A hidden item must not count as outstanding work.');
        $this->assertTrue($summary['is_graded']);
        $this->assertEqualsWithDelta(88.0, $summary['final_grade'], 0.01);
    }

    public function test_incomplete_reason_names_what_is_missing(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 50, GradeItem::TYPE_EXAM => 50]);

        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 1), 90);
        $this->item(GradeItem::TYPE_QUIZ, 'Quiz 2', 2);
        $this->item(GradeItem::TYPE_EXAM, 'Final Exam', 3);

        $summary = $this->summary();

        $this->assertNotNull($summary['incomplete_reason']);
        $this->assertStringContainsString('2', $summary['incomplete_reason']);
    }

    public function test_provisional_score_is_still_available_while_incomplete(): void
    {
        // Staff still need to see where the student stands mid-term.
        $this->configure([GradeItem::TYPE_QUIZ => 50, GradeItem::TYPE_EXAM => 50]);

        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 1), 90);

        $summary = $this->summary();

        $this->assertFalse($summary['is_graded']);
        $this->assertNotNull($summary['provisional_grade']);
        $this->assertEqualsWithDelta(90.0, $summary['provisional_grade'], 0.01);
    }

    public function test_risk_status_reads_incomplete_not_pending_when_work_is_missing(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 50, GradeItem::TYPE_EXAM => 50]);

        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 1), 90);

        $risk = app(\App\Services\StudentRiskService::class)
            ->evaluate($this->class, $this->summary());

        $this->assertSame('Incomplete', $risk['label']);
        $this->assertTrue($risk['has_data'], 'The student does have graded work.');
    }

    public function test_risk_status_is_pending_when_nothing_is_graded_at_all(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 100]);

        $this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 1);

        $risk = app(\App\Services\StudentRiskService::class)
            ->evaluate($this->class, $this->summary());

        $this->assertSame('Pending', $risk['label']);
        $this->assertFalse($risk['has_data']);
    }
}