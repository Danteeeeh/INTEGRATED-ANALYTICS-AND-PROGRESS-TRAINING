<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * DemoDataSeeder invents "Midterm Exam" / "Final Exam" grade items with fixed
 * percentages and no backing Exam record, so students saw exam results for
 * exams that do not exist.
 *
 * The purge must key off the seeder's fingerprint, never off the title, so a
 * genuine item called "Final Exam" survives.
 */
class PurgeDemoGradesTest extends TestCase
{
    use RefreshDatabase;

    /** The feedback string DemoDataSeeder stamps on every fabricated grade. */
    private const MARKER = 'Keep up the good work. Review the areas where you scored below target.';

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['title' => 'Mathematics in the Modern World']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->student = $student;
    }

    private function grade(string $title, ?string $feedback, float $percent = 80): Grade
    {
        $item = GradeItem::create([
            'class_id' => $this->class->id,
            'title' => $title,
            'max_points' => 100,
            'factor' => 0.3,
            'item_type' => 'exam',
            'position' => 0,
            'is_released' => true,
        ]);

        return Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $this->student->id,
            'points' => $percent,
            'score_percent' => $percent,
            'feedback' => $feedback,
            'graded_at' => now()->subDay(),
        ]);
    }

    public function test_fabricated_grades_and_their_items_are_removed(): void
    {
        $grade = $this->grade('Midterm Exam', self::MARKER, 74);

        Artisan::call('lms:purge-demo-grades --apply');

        $this->assertNull(Grade::find($grade->id));
        $this->assertNull(
            GradeItem::find($grade->grade_item_id),
            'An item that only existed to carry the fake grade should go too.'
        );
    }

    public function test_a_real_grade_titled_final_exam_is_untouched(): void
    {
        // Same title, real instructor feedback: must survive.
        $real = $this->grade('Final Exam', 'Good work, review chapter 3.');

        Artisan::call('lms:purge-demo-grades --apply');

        $this->assertNotNull(Grade::find($real->id), 'A genuine grade must never be deleted.');
        $this->assertNotNull(GradeItem::find($real->grade_item_id));
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $grade = $this->grade('Final Exam', self::MARKER, 80);

        Artisan::call('lms:purge-demo-grades');

        $this->assertStringContainsString('Dry run', Artisan::output());
        $this->assertNotNull(Grade::find($grade->id), 'Dry run must not delete.');
    }

    public function test_keep_items_preserves_the_grade_items(): void
    {
        $grade = $this->grade('Final Exam', self::MARKER, 80);

        Artisan::call('lms:purge-demo-grades --apply --keep-items');

        $this->assertNull(Grade::find($grade->id));
        $this->assertNotNull(
            GradeItem::find($grade->grade_item_id),
            '--keep-items must leave the item in place.'
        );
    }

    public function test_item_is_kept_when_a_real_grade_still_uses_it(): void
    {
        // One shared item carrying two students: one fake grade, one real.
        $item = GradeItem::create([
            'class_id' => $this->class->id,
            'title' => 'Midterm Exam',
            'max_points' => 100,
            'factor' => 0.3,
            'item_type' => 'exam',
            'position' => 0,
            'is_released' => true,
        ]);

        $other = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $fake = Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $this->student->id,
            'points' => 74,
            'score_percent' => 74,
            'feedback' => self::MARKER,
            'graded_at' => now()->subDay(),
        ]);

        Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $other->id,
            'points' => 91,
            'score_percent' => 91,
            'feedback' => 'Real feedback here.',
            'graded_at' => now()->subDay(),
        ]);

        Artisan::call('lms:purge-demo-grades --apply');

        $this->assertNull(Grade::find($fake->id));
        $this->assertNotNull(
            GradeItem::find($item->id),
            'An item still referenced by a real grade must not be deleted.'
        );
    }

    public function test_reports_nothing_to_do_on_clean_data(): void
    {
        $this->grade('Final Exam', 'Genuine feedback.');

        Artisan::call('lms:purge-demo-grades --apply');

        $output = Artisan::output();

        $this->assertStringContainsString('No fabricated demo grades found', $output);
    }

    public function test_fabricated_100_percent_quiz_attempt_is_removed_only_when_requested(): void
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $quiz = \App\Models\Quiz::create([
            'class_id' => $this->class->id,
            'title' => 'Week 2 quiz',
            'slug' => 'week-2-'.$this->class->id,
            'created_by' => $instructor->id,
            'status' => 'published',
        ]);

        $questionId = $this->seedQuestion();

        $attempt = \App\Models\QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'score' => 3,
            'score_percent' => 100,
            'status' => 'graded',
            'graded_by' => $instructor->id,
            'graded_at' => now()->subDays(4),
            'started_at' => now()->subDays(5),
            'submitted_at' => now()->subDays(5),
        ]);

        \App\Models\QuizAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'points_awarded' => 1,
            'is_correct' => true,
        ]);

        // Without the flag the attempts must be left alone.
        Artisan::call('lms:purge-demo-grades --apply');
        $this->assertNotNull(\App\Models\QuizAttempt::find($attempt->id));

        Artisan::call('lms:purge-demo-grades --apply --quiz-attempts');

        $this->assertNull(
            \App\Models\QuizAttempt::find($attempt->id),
            'A 100% attempt with every answer correct is the seeder fingerprint.'
        );
    }

    public function test_a_real_attempt_with_a_mistake_is_never_removed(): void
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $quiz = \App\Models\Quiz::create([
            'class_id' => $this->class->id,
            'title' => 'Real quiz',
            'slug' => 'real-'.$this->class->id,
            'created_by' => $instructor->id,
            'status' => 'published',
        ]);

        $questionId = $this->seedQuestion();

        $attempt = \App\Models\QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'score' => 3,
            'score_percent' => 100,
            'status' => 'graded',
            'graded_by' => $instructor->id,
            'graded_at' => now()->subDays(4),
            'started_at' => now()->subDays(5),
            'submitted_at' => now()->subDays(5),
        ]);

        // One wrong answer: a real student, not the seeder.
        \App\Models\QuizAnswer::create([
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'points_awarded' => 0,
            'is_correct' => false,
        ]);

        Artisan::call('lms:purge-demo-grades --apply --quiz-attempts');

        $this->assertNotNull(\App\Models\QuizAttempt::find($attempt->id));
    }

    /** quiz_answers.question_id is a real FK, so seed an actual question. */
    private function seedQuestion(): int
    {
        $bank = \App\Models\QuestionBank::create([
            'title' => 'Bank '.uniqid(),
            'created_by' => $this->student->id,
            'status' => 'active',
        ]);

        return \App\Models\Question::create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Seeded question',
            'created_by' => $this->student->id,
            'question_type' => 'true_false',
            'points' => 1,
        ])->id;
    }}
