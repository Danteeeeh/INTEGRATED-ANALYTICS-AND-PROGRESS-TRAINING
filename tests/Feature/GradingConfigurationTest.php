<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeConfiguration;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Services\GradeBreakdownService;
use App\Services\StudentRiskService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $otherInstructor;

    protected User $student;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->otherInstructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->student = User::factory()->student()->create(['status' => 'active']);

        $course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'code' => 'IT-101',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
    }

    protected function item(string $type, string $title, float $maxPoints = 100, int $position = 0): GradeItem
    {
        return GradeItem::create([
            'class_id' => $this->class->id,
            'title' => $title,
            'item_type' => $type,
            'max_points' => $maxPoints,
            'factor' => 1,
            'is_released' => true,
            'released_at' => now(),
            'position' => $position,
        ]);
    }

    protected function grade(GradeItem $item, float $points, ?User $student = null): Grade
    {
        return Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => ($student ?? $this->student)->id,
            'points' => $points,
            'score_percent' => $points,
        ]);
    }

    protected function configure(array $weights): GradeConfiguration
    {
        $attributes = ['class_id' => $this->class->id, 'created_by' => $this->instructor->id];

        foreach (GradeConfiguration::COMPONENTS as $type => $meta) {
            $attributes[$meta['column']] = $weights[$type] ?? 0;
        }

        return GradeConfiguration::create($attributes);
    }

    // ── The 100% validation ─────────────────────────────────────

    public function test_weights_that_total_100_are_valid(): void
    {
        $config = $this->configure([
            GradeItem::TYPE_QUIZ => 20,
            GradeItem::TYPE_ASSIGNMENT => 25,
            GradeItem::TYPE_EXAM => 40,
            GradeItem::TYPE_ATTENDANCE => 15,
        ]);

        $this->assertTrue($config->isValid());
        $this->assertSame(100.0, $config->totalWeight());
        $this->assertNull($config->validationMessage());
    }

    public function test_unticked_components_do_not_count_toward_the_total(): void
    {
        // Only the three ticked components matter, so 85 is the real total.
        $config = $this->configure([
            GradeItem::TYPE_QUIZ => 20,
            GradeItem::TYPE_ASSIGNMENT => 25,
            GradeItem::TYPE_EXAM => 40,
        ]);

        $this->assertSame(85.0, $config->totalWeight());
        $this->assertFalse($config->isValid(), '85 is not 100.');
        $this->assertSame([], array_diff(array_keys($config->enabledWeights()), [
            GradeItem::TYPE_QUIZ, GradeItem::TYPE_ASSIGNMENT, GradeItem::TYPE_EXAM,
        ]));
    }

    public function test_weights_that_do_not_total_100_are_rejected_with_a_message(): void
    {
        $config = $this->configure([
            GradeItem::TYPE_QUIZ => 20,
            GradeItem::TYPE_ASSIGNMENT => 25,
        ]);

        $this->assertFalse($config->isValid());
        $this->assertStringContainsString('45', $config->validationMessage());
        $this->assertStringContainsString('add', $config->validationMessage());
    }

    public function test_weights_over_100_tell_the_instructor_to_remove(): void
    {
        $config = $this->configure([
            GradeItem::TYPE_QUIZ => 60,
            GradeItem::TYPE_EXAM => 60,
        ]);

        $this->assertStringContainsString('remove', $config->validationMessage());
    }

    public function test_an_empty_configuration_is_invalid(): void
    {
        $config = $this->configure([]);

        $this->assertFalse($config->isValid());
        $this->assertStringContainsString('at least one', $config->validationMessage());
    }

    public function test_an_invalid_configuration_is_never_used_for_grading(): void
    {
        $item = $this->item(GradeItem::TYPE_QUIZ, 'Quiz 1');
        $this->grade($item, 100);

        // 90% only — not a valid configuration.
        $this->configure([GradeItem::TYPE_QUIZ => 90]);

        $this->class->refresh();

        $this->assertNull(
            $this->class->activeGradeConfiguration(),
            'An unbalanced configuration must not change how grades are computed.'
        );
    }

    // ── Attendance is opt-in ─────────────────────────────────────

    public function test_attendance_defaults_to_zero_weight(): void
    {
        $config = GradeConfiguration::create(['class_id' => $this->class->id]);

        $this->assertSame(0.0, $config->weights()[GradeItem::TYPE_ATTENDANCE]);
        $this->assertFalse($config->gradesAttendance(), 'Attendance is never weighted automatically.');
    }

    public function test_attendance_only_counts_when_the_instructor_ticks_it(): void
    {
        $item = $this->item(GradeItem::TYPE_QUIZ, 'Quiz 1');
        $this->grade($item, 80);

        // Attendance not graded → a 20% attendance record is ignored entirely.
        AttendanceRecord::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $this->configure([GradeItem::TYPE_QUIZ => 100]);

        $row = app(GradeBreakdownService::class)->forClass($this->class->fresh())[$this->student->id];

        $this->assertEqualsWithDelta(80.0, (float) $row['final_grade'], 0.01);
        $this->assertArrayNotHasKey(
            GradeItem::TYPE_ATTENDANCE,
            array_filter($row['components'], fn ($c) => ($c['weight'] ?? 0) > 0)
        );
    }

    public function test_attendance_is_weighted_when_ticked(): void
    {
        $item = $this->item(GradeItem::TYPE_QUIZ, 'Quiz 1');
        $this->grade($item, 100);

        AttendanceRecord::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'absent',
        ]);

        $this->configure([
            GradeItem::TYPE_QUIZ => 85,
            GradeItem::TYPE_ATTENDANCE => 15,
        ]);

        $row = app(GradeBreakdownService::class)->forClass($this->class->fresh())[$this->student->id];

        // 85% of 100% quiz + 15% of 0% attendance = 85%
        $this->assertEqualsWithDelta(85.0, (float) $row['final_grade'], 0.01);
        $this->assertSame(0.0, (float) $row['components'][GradeItem::TYPE_ATTENDANCE]['percent']);
    }

    // ── Weighted final grade ────────────────────────────────────

    public function test_final_grade_uses_the_configured_weights(): void
    {
        // Quizzes 20% at 50, Assignments 25% at 100, Exams 40% at 90
        // (15% of nothing) → (0.20*50) + (0.25*100) + (0.40*90) = 71
        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 100, 0), 50);
        $this->grade($this->item(GradeItem::TYPE_ASSIGNMENT, 'Assign 1', 100, 1), 100);
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam 1', 100, 2), 90);

        $this->configure([
            GradeItem::TYPE_QUIZ => 20,
            GradeItem::TYPE_ASSIGNMENT => 25,
            GradeItem::TYPE_EXAM => 40,
            GradeItem::TYPE_ATTENDANCE => 15,
        ]);

        $row = app(GradeBreakdownService::class)->forClass($this->class->fresh())[$this->student->id];

        // Attendance has no records yet, so the graded weights are renormalised
        // over the 85 that do have data: (0.20*50 + 0.25*100 + 0.40*90) / 0.85
        //
        // That figure is now reported as provisional, not as the term grade:
        // a weighted component with no records is outstanding work, so no
        // final grade is published until attendance is recorded.
        $this->assertEqualsWithDelta(83.53, (float) $row['provisional_grade'], 0.05);

        $this->assertFalse($row['is_graded'], 'Attendance has no records, so the grade is not final.');
        $this->assertNull($row['final_grade']);
        $this->assertSame(1, $row['missing_items']);
    }

    public function test_component_columns_are_exposed(): void
    {
        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 100, 0), 80);
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam 1', 100, 1), 90);

        $this->configure([
            GradeItem::TYPE_QUIZ => 30,
            GradeItem::TYPE_EXAM => 70,
        ]);

        $row = app(GradeBreakdownService::class)->forClass($this->class->fresh())[$this->student->id];

        $this->assertArrayHasKey(GradeItem::TYPE_QUIZ, $row['components']);
        $this->assertArrayHasKey(GradeItem::TYPE_EXAM, $row['components']);
        $this->assertSame(30.0, $row['components'][GradeItem::TYPE_QUIZ]['weight']);
        $this->assertEqualsWithDelta(80.0, (float) $row['components'][GradeItem::TYPE_QUIZ]['percent'], 0.01);
        $this->assertEqualsWithDelta(
            0.30 * 80 + 0.70 * 90,
            (float) $row['final_grade'],
            0.01
        );
    }

    public function test_adding_a_second_quiz_does_not_change_the_exam_weight(): void
    {
        $this->configure([
            GradeItem::TYPE_QUIZ => 20,
            GradeItem::TYPE_EXAM => 80,
        ]);

        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 100, 0), 100);
        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 2', 100, 1), 0);
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 100, 2), 100);

        $row = app(GradeBreakdownService::class)->forClass($this->class->fresh())[$this->student->id];

        // Quiz component drops to 50% (100 and 0 of 100), exam stays 100%.
        $this->assertEqualsWithDelta(50.0, (float) $row['components'][GradeItem::TYPE_QUIZ]['percent'], 0.01);
        $this->assertSame(80.0, $row['components'][GradeItem::TYPE_EXAM]['weight']);
        $this->assertEqualsWithDelta(0.20 * 50 + 0.80 * 100, (float) $row['final_grade'], 0.01);
    }

    public function test_unreleased_items_never_affect_the_grade(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 100]);

        $released = $this->item(GradeItem::TYPE_QUIZ, 'Released', 100, 0);
        $this->grade($released, 90);

        $hidden = GradeItem::create([
            'class_id' => $this->class->id,
            'title' => 'Hidden',
            'item_type' => GradeItem::TYPE_QUIZ,
            'max_points' => 100,
            'factor' => 1,
            'is_released' => false,
            'position' => 1,
        ]);

        $this->grade($hidden, 0);

        $row = app(GradeBreakdownService::class)->forClass($this->class->fresh())[$this->student->id];

        $this->assertEqualsWithDelta(90.0, (float) $row['final_grade'], 0.01);
    }

    public function test_a_class_with_no_configuration_keeps_its_original_grade(): void
    {
        // Regression guard: legacy classes must not change behaviour.
        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Q1', 100, 0), 80);
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'E1', 100, 1), 100);

        $row = app(GradeBreakdownService::class)->forClass($this->class->fresh())[$this->student->id];

        $this->assertEqualsWithDelta(90.0, (float) $row['final_grade'], 0.01);
    }

    public function test_a_student_with_no_grades_is_pending(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 100]);
        $this->item(GradeItem::TYPE_QUIZ, 'Quiz 1');

        $row = app(GradeBreakdownService::class)->forClass($this->class->fresh())[$this->student->id];

        $this->assertFalse($row['is_graded']);

        $risk = app(StudentRiskService::class)->evaluate($this->class, $row);

        $this->assertSame('pending', $risk['status']);
        $this->assertFalse($risk['has_data']);
    }

    // ── On Track / At Risk ──────────────────────────────────────

    public function test_a_healthy_student_is_on_track(): void
    {
        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 100, 0), 90);
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 100, 1), 95);

        $this->configure([
            GradeItem::TYPE_QUIZ => 40,
            GradeItem::TYPE_EXAM => 60,
        ]);

        $class = $this->class->fresh();
        $row = app(GradeBreakdownService::class)->forClass($class)[$this->student->id];

        $risk = app(StudentRiskService::class)->evaluate($class, $row);

        $this->assertSame('passed', $risk['status']);
        $this->assertSame('Passed', $risk['label']);
        $this->assertSame([], $risk['reasons']);
    }

    public function test_a_weak_component_triggers_at_risk_with_a_reason(): void
    {
        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 100, 0), 95);
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 100, 1), 30);

        $this->configure([
            GradeItem::TYPE_QUIZ => 20,
            GradeItem::TYPE_EXAM => 80,
        ]);

        $class = $this->class->fresh();
        $row = app(GradeBreakdownService::class)->forClass($class)[$this->student->id];

        $risk = app(StudentRiskService::class)->evaluate($class, $row);

        $this->assertSame('at_risk', $risk['status']);
        $this->assertNotEmpty($risk['reasons']);
        $this->assertStringContainsString('Exams', implode(' ', $risk['reasons']));
    }

    public function test_low_attendance_triggers_at_risk_only_when_graded(): void
    {
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 100, 0), 95);

        for ($i = 0; $i < 8; $i++) {
            AttendanceRecord::create([
                'class_id' => $this->class->id,
                'student_id' => $this->student->id,
                'attendance_date' => now()->subDays($i + 1)->toDateString(),
                'status' => $i < 2 ? 'present' : 'absent',
            ]);
        }

        // Attendance weighted → 25% triggers the 75% floor.
        $this->configure([
            GradeItem::TYPE_EXAM => 85,
            GradeItem::TYPE_ATTENDANCE => 15,
        ]);

        $class = $this->class->fresh();
        $row = app(GradeBreakdownService::class)->forClass($class)[$this->student->id];
        $risk = app(StudentRiskService::class)->evaluate($class, $row);

        $this->assertSame('at_risk', $risk['status']);
        $this->assertStringContainsString('Attendance', implode(' ', $risk['reasons']));
    }

    public function test_failing_final_grade_is_reported(): void
    {
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 100, 0), 40);

        $this->configure([GradeItem::TYPE_EXAM => 100]);

        $class = $this->class->fresh();
        $row = app(GradeBreakdownService::class)->forClass($class)[$this->student->id];
        $risk = app(StudentRiskService::class)->evaluate($class, $row);

        $this->assertSame('at_risk', $risk['status']);
        $this->assertStringContainsString('passing mark', implode(' ', $risk['reasons']));
    }

    public function test_low_lesson_progress_triggers_at_risk(): void
    {
        $module = Module::factory()->create([
            'course_id' => $this->class->course_id,
            'status' => 'published',
        ]);

        $lessons = Lesson::factory()->count(4)->create(['module_id' => $module->id]);

        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 100, 0), 95);

        // One of four lessons done → 25% progress.
        LessonProgress::create([
            'lesson_id' => $lessons->first()->id,
            'student_id' => $this->student->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
        ]);

        $this->configure([GradeItem::TYPE_EXAM => 100]);

        $class = $this->class->fresh();
        $row = app(GradeBreakdownService::class)->forClass($class)[$this->student->id];
        $risk = app(StudentRiskService::class)->evaluate($class, $row);

        $this->assertSame('at_risk', $risk['status']);
        $this->assertStringContainsString('progress', strtolower(implode(' ', $risk['reasons'])));
    }

    public function test_tally_counts_at_risk_students(): void
    {
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 100, 0), 95);
        $this->configure([GradeItem::TYPE_EXAM => 100]);

        $class = $this->class->fresh();
        $breakdowns = app(GradeBreakdownService::class)->forClass($class);
        $risk = app(StudentRiskService::class)->evaluateAll($class, $breakdowns);
        $tally = app(StudentRiskService::class)->tally($risk);

        $this->assertSame(1, $tally['total']);
        $this->assertSame(0, $tally['at_risk']);
        $this->assertSame(1, $tally['passed']);
    }

    // ── Controller ──────────────────────────────────────────────

    public function test_instructor_can_open_the_configuration_screen(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.classes.gradebook.grading-config.edit', $this->class))
            ->assertOk()
            ->assertSee('Grading Configuration', false)
            ->assertSee('Quizzes', false)
            ->assertSee('Virtual Class Attendance', false);
    }

    public function test_saving_balanced_weights_succeeds(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.classes.gradebook.grading-config.update', $this->class), [
                'weights' => [
                    GradeItem::TYPE_QUIZ => 20,
                    GradeItem::TYPE_ASSIGNMENT => 25,
                    GradeItem::TYPE_EXAM => 40,
                    GradeItem::TYPE_ATTENDANCE => 15,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $config = GradeConfiguration::where('class_id', $this->class->id)->firstOrFail();

        $this->assertTrue($config->isValid());
        $this->assertSame(20.0, $config->weights()[GradeItem::TYPE_QUIZ]);
        $this->assertSame(15.0, $config->weights()[GradeItem::TYPE_ATTENDANCE]);
    }

    public function test_saving_unbalanced_weights_is_refused_with_an_explanation(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.classes.gradebook.grading-config.update', $this->class), [
                'weights' => [
                    GradeItem::TYPE_QUIZ => 20,
                    GradeItem::TYPE_EXAM => 40,
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $config = GradeConfiguration::where('class_id', $this->class->id)->firstOrFail();

        // Saved so the work is not lost, but flagged as unusable.
        $this->assertFalse($config->isValid());
    }

    public function test_out_of_range_weights_are_rejected(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.classes.gradebook.grading-config.update', $this->class), [
                'weights' => [GradeItem::TYPE_QUIZ => 500],
            ])
            ->assertSessionHasErrors('weights.quiz');
    }

    public function test_unknown_components_are_rejected(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.classes.gradebook.grading-config.update', $this->class), [
                'weights' => ['hacking_the_grade' => 100],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('grade_configurations', ['class_id' => $this->class->id]);
    }

    public function test_configuration_can_be_removed(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 100]);

        $this->actingAs($this->instructor)
            ->delete(route('instructor.classes.gradebook.grading-config.destroy', $this->class))
            ->assertRedirect();

        $this->assertDatabaseMissing('grade_configurations', ['class_id' => $this->class->id]);
    }

    public function test_another_instructor_cannot_configure_this_class(): void
    {
        $this->actingAs($this->otherInstructor)
            ->get(route('instructor.classes.gradebook.grading-config.edit', $this->class))
            ->assertForbidden();

        $this->actingAs($this->otherInstructor)
            ->post(route('instructor.classes.gradebook.grading-config.update', $this->class), [
                'weights' => [GradeItem::TYPE_QUIZ => 100],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('grade_configurations', ['class_id' => $this->class->id]);
    }

    public function test_gradebook_shows_component_columns_and_status(): void
    {
        $this->grade($this->item(GradeItem::TYPE_QUIZ, 'Quiz 1', 100, 0), 90);
        $this->grade($this->item(GradeItem::TYPE_EXAM, 'Exam', 100, 1), 30);

        $this->configure([
            GradeItem::TYPE_QUIZ => 20,
            GradeItem::TYPE_EXAM => 80,
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.classes.gradebook.index', $this->class))
            ->assertOk()
            ->assertSee('Final Grade', false)
            ->assertSee('Status', false)
            ->assertSee('At Risk', false)
            ->assertSee('Grading Configuration', false);
    }

    public function test_gradebook_shows_the_unbalanced_configuration_warning(): void
    {
        $this->configure([GradeItem::TYPE_QUIZ => 40]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.classes.gradebook.index', $this->class))
            ->assertOk()
            ->assertSee('not in use', false);
    }
}