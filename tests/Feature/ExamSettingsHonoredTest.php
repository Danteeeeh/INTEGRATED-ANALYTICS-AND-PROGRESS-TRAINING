<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\GradeItem;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\User;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * Exam settings used to be stored without changing anything a student saw.
 * These tests cover the settings that now drive delivery (choice order and
 * navigation), proctoring beacons, and what the result page is allowed to show.
 */
class ExamSettingsHonoredTest extends TestCase
{
    use CreatesLmsUsers;
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private Course $course;

    private ClassModel $class;

    private Question $question;

    private QuestionChoice $wrongChoice;

    private QuestionChoice $rightChoice;

    private QuestionChoice $extraChoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = $this->makeUser('instructor');
        $this->student = $this->makeUser('student');

        $this->course = Course::factory()->create([
            'status' => 'published',
            'created_by' => $this->instructor->id,
        ]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(3),
        ]);

        $bank = QuestionBank::create([
            'course_id' => $this->course->id,
            'title' => 'Settings bank',
            'created_by' => $this->instructor->id,
            'status' => 'active',
        ]);

        $this->question = Question::create([
            'question_bank_id' => $bank->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'What is the capital of France?',
            'default_points' => 10,
            'created_by' => $this->instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        $this->wrongChoice = QuestionChoice::create([
            'question_id' => $this->question->id,
            'choice_text' => 'Lyon',
            'is_correct' => false,
            'position' => 1,
            'points' => 0,
        ]);

        $this->rightChoice = QuestionChoice::create([
            'question_id' => $this->question->id,
            'choice_text' => 'Paris',
            'is_correct' => true,
            'position' => 2,
            'points' => 0,
        ]);

        $this->extraChoice = QuestionChoice::create([
            'question_id' => $this->question->id,
            'choice_text' => 'Marseille',
            'is_correct' => false,
            'position' => 3,
            'points' => 0,
        ]);
    }

    private function makeExam(array $overrides = []): Exam
    {
        $exam = Exam::create(array_merge([
            'class_id' => $this->class->id,
            'course_id' => $this->course->id,
            'title' => 'Settings exam',
            'slug' => 'settings-exam-'.uniqid(),
            'exam_type' => Exam::TYPE_PRELIM,
            'duration_minutes' => 60,
            'total_points' => 10,
            'result_visibility' => Exam::VISIBILITY_AFTER_GRADING,
            'show_score' => true,
            'show_correct_answers' => false,
            'allow_review' => true,
            'allow_navigation' => true,
            'shuffle_choices' => false,
            'status' => Exam::STATUS_PUBLISHED,
            'created_by' => $this->instructor->id,
        ], $overrides));

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $this->question->id,
            'order' => 1,
            'points' => 10,
            'is_required' => true,
        ]);

        return $exam;
    }

    /**
     * Attach extra questions so question-order tests have something to reorder.
     *
     * @return list<int>
     */
    private function attachExtraQuestions(Exam $exam, int $count): array
    {
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $question = Question::create([
                'question_bank_id' => $this->question->question_bank_id,
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'Extra question '.($i + 1),
                'default_points' => 5,
                'created_by' => $this->instructor->id,
                'status' => Question::STATUS_ACTIVE,
            ]);

            QuestionChoice::create([
                'question_id' => $question->id,
                'choice_text' => 'Wrong '.($i + 1),
                'is_correct' => false,
                'position' => 1,
                'points' => 0,
            ]);

            QuestionChoice::create([
                'question_id' => $question->id,
                'choice_text' => 'Right '.($i + 1),
                'is_correct' => true,
                'position' => 2,
                'points' => 0,
            ]);

            $exam->questions()->attach($question->id, [
                'order' => $i + 2,
                'points' => 5,
                'is_required' => true,
            ]);

            $ids[] = $question->id;
        }

        return $ids;
    }

    private function startExam(Exam $exam): ExamAttempt
    {
        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))
            ->assertOk();

        return ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $this->student->id)
            ->firstOrFail();
    }

    private function submitExam(Exam $exam, ExamAttempt $attempt): void
    {
        $this->actingAs($this->student)
            ->post(route('student.courses.exams.attempt.store', [$this->course, $exam]), [
                'answers' => [$this->question->id => $this->rightChoice->id],
            ])
            ->assertRedirect(route('student.courses.exams.attempts.show', [$this->course, $exam, $attempt]));
    }

    public function test_choice_order_setting_controls_the_frozen_snapshot_order(): void
    {
        $fixed = $this->makeExam(['shuffle_choices' => false]);
        $fixedAttempt = $this->startExam($fixed);
        $fixedSnapshot = $fixedAttempt->answers()->firstOrFail()->snapshotArray();

        $this->assertSame(
            [$this->wrongChoice->id, $this->rightChoice->id, $this->extraChoice->id],
            array_column($fixedSnapshot['choices'], 'id'),
            'With shuffling off, the snapshot must preserve the bank order.'
        );

        $shuffled = $this->makeExam([
            'title' => 'Shuffled choices exam',
            'slug' => 'settings-exam-shuffled-'.uniqid(),
            'shuffle_choices' => true,
        ]);
        $shuffledAttempt = $this->startExam($shuffled);
        $shuffledSnapshot = $shuffledAttempt->answers()->firstOrFail()->snapshotArray();

        $snapshotIds = array_column($shuffledSnapshot['choices'], 'id');
        sort($snapshotIds);

        $expectedIds = [$this->wrongChoice->id, $this->rightChoice->id, $this->extraChoice->id];
        sort($expectedIds);

        $this->assertSame($expectedIds, $snapshotIds, 'Shuffling must keep every choice, only in another order.');
        $this->assertSame((int) $this->rightChoice->id, (int) $shuffledSnapshot['correct_answer']);
        $this->assertContains('Paris', $shuffledSnapshot['accepted_answers']);
    }

    public function test_question_order_setting_controls_the_frozen_attempt_order(): void
    {
        $fixed = $this->makeExam(['shuffle_questions' => false]);
        $extraIds = $this->attachExtraQuestions($fixed, 8);
        $fixedAttempt = $this->startExam($fixed);

        $this->assertSame(
            array_merge([$this->question->id], $extraIds),
            $fixedAttempt->answers()->pluck('question_id')->all(),
            'With shuffling off, the attempt must preserve the exam question order.'
        );

        $shuffled = $this->makeExam([
            'title' => 'Shuffled questions exam',
            'slug' => 'settings-exam-shuffled-questions-'.uniqid(),
            'shuffle_questions' => true,
        ]);
        $shuffledExtraIds = $this->attachExtraQuestions($shuffled, 8);
        $shuffledAttempt = $this->startExam($shuffled);

        $expectedIds = array_merge([$this->question->id], $shuffledExtraIds);
        $actualIds = $shuffledAttempt->answers()->pluck('question_id')->all();

        $this->assertEqualsCanonicalizing($expectedIds, $actualIds, 'Shuffling must keep every question.');
        $this->assertNotSame($expectedIds, $actualIds, 'Shuffling should change at least one question position.');
    }

    public function test_navigation_is_hidden_and_locked_when_allow_navigation_is_off(): void
    {
        $exam = $this->makeExam(['allow_navigation' => false]);

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('const allowNavigation = false;', false)
            ->assertDontSee('Question Navigation')
            ->assertDontSee('id="prevBtn"', false);

        $this->assertStringContainsString('function prevQuestion()', $response->getContent());
    }

    public function test_navigation_is_available_when_allow_navigation_is_on(): void
    {
        $exam = $this->makeExam(['allow_navigation' => true]);

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('const allowNavigation = true;', false)
            ->assertSee('Question Navigation')
            ->assertSee('id="prevBtn"', false);
    }

    public function test_proctoring_events_are_recorded_and_visible_to_the_instructor(): void
    {
        $exam = $this->makeExam([
            'requires_proctoring' => true,
            'proctoring_method' => 'online',
            'proctoring_instructions' => 'Stay on this page and keep your camera on.',
            'detect_tab_switch' => true,
            'detect_copy_paste' => true,
        ]);

        $attempt = $this->startExam($exam);

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('Proctored exam')
            ->assertSee('Method: Online')
            ->assertSee('Stay on this page and keep your camera on.');

        $this->actingAs($this->student)
            ->postJson(route('student.courses.exams.attempt.proctor', [$this->course, $exam]), [
                'event' => 'tab_switch',
            ])
            ->assertNoContent();

        $this->actingAs($this->student)
            ->postJson(route('student.courses.exams.attempt.proctor', [$this->course, $exam]), [
                'event' => 'copy_paste',
            ])
            ->assertNoContent();

        $attempt->refresh();

        $this->assertSame(1, $attempt->tab_switch_count);
        $this->assertSame(1, $attempt->suspicious_activity_count);
        $this->assertCount(2, $attempt->proctoring_log);
        $this->assertSame('tab_switch', $attempt->proctoring_log[0]['event']);
        $this->assertSame('copy_paste', $attempt->proctoring_log[1]['event']);

        $this->actingAs($this->instructor)
            ->get(route('instructor.courses.exams.show', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('tab switch(es)')
            ->assertSee('clipboard event(s)');
    }

    public function test_proctoring_method_and_instructions_are_shown_even_without_the_requires_flag(): void
    {
        $exam = $this->makeExam([
            'requires_proctoring' => false,
            'proctoring_method' => 'online',
            'proctoring_instructions' => 'Join the proctoring room before starting.',
        ]);

        $this->startExam($exam);

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('Method: Online')
            ->assertSee('Join the proctoring room before starting.');
    }

    public function test_proctoring_events_are_ignored_when_the_detector_is_off(): void
    {
        $exam = $this->makeExam([
            'detect_tab_switch' => false,
            'detect_copy_paste' => false,
        ]);

        $attempt = $this->startExam($exam);

        $this->actingAs($this->student)
            ->postJson(route('student.courses.exams.attempt.proctor', [$this->course, $exam]), [
                'event' => 'tab_switch',
            ])
            ->assertNoContent();

        $this->actingAs($this->student)
            ->postJson(route('student.courses.exams.attempt.proctor', [$this->course, $exam]), [
                'event' => 'copy_paste',
            ])
            ->assertNoContent();

        $attempt->refresh();

        $this->assertSame(0, $attempt->tab_switch_count);
        $this->assertSame(0, $attempt->suspicious_activity_count);
        $this->assertNull($attempt->proctoring_log);
    }

    public function test_a_student_outside_the_class_cannot_send_proctoring_events(): void
    {
        $exam = $this->makeExam([
            'requires_proctoring' => true,
            'detect_tab_switch' => true,
        ]);

        $outsider = $this->makeUser('student');

        $this->actingAs($outsider)
            ->postJson(route('student.courses.exams.attempt.proctor', [$this->course, $exam]), [
                'event' => 'tab_switch',
            ])
            ->assertForbidden();
    }

    public function test_show_score_off_hides_the_score_and_answer_key(): void
    {
        $exam = $this->makeExam([
            'show_score' => false,
            'show_correct_answers' => false,
            'allow_review' => true,
        ]);

        $attempt = $this->startExam($exam);
        $this->submitExam($exam, $attempt);

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempts.show', [$this->course, $exam, $attempt]))
            ->assertOk()
            ->assertSee('Hidden by your instructor')
            ->assertDontSee('100.00%')
            ->assertDontSee('text-4xl font-bold')
            ->assertDontSee('Passed')
            ->assertSee('Question Breakdown')
            ->assertDontSee('Correct Answer:');

        // The gradebook is another student-facing score surface. A hidden exam
        // score must not be released there behind the result page's back.
        $this->assertDatabaseHas('grade_items', [
            'related_type' => Exam::class,
            'related_id' => $exam->id,
            'is_released' => 0,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.classes.gradebook.index', $this->class))
            ->assertOk()
            ->assertDontSee('Settings exam');

        app(ExamService::class)->updateExam($exam, ['show_score' => true]);

        $this->assertDatabaseHas('grade_items', [
            'related_type' => Exam::class,
            'related_id' => $exam->id,
            'is_released' => 1,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.classes.gradebook.index', $this->class))
            ->assertOk()
            ->assertSee('Settings exam');
    }

    public function test_show_correct_answers_reveals_the_frozen_answer_key(): void
    {
        $exam = $this->makeExam([
            'show_score' => true,
            'show_correct_answers' => true,
            'allow_review' => true,
        ]);

        $attempt = $this->startExam($exam);
        $this->submitExam($exam, $attempt);

        // The Test Bank is edited after submission; the result must still show
        // the answer the student was actually graded against.
        $this->question->update(['question_text' => 'Rewritten after the exam']);
        $this->rightChoice->update(['choice_text' => 'Rewritten answer']);

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempts.show', [$this->course, $exam, $attempt]))
            ->assertOk()
            ->assertSee('100.00%')
            ->assertSee('Correct Answer:')
            ->assertSee('Paris')
            ->assertDontSee('Rewritten answer');

        $this->assertDatabaseHas('grade_items', [
            'related_type' => Exam::class,
            'related_id' => $exam->id,
            'is_released' => 1,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.classes.gradebook.index', $this->class))
            ->assertOk()
            ->assertSee('Settings exam');
    }

    public function test_allow_review_off_hides_the_question_breakdown(): void
    {
        $exam = $this->makeExam([
            'show_score' => true,
            'show_correct_answers' => true,
            'allow_review' => false,
        ]);

        $attempt = $this->startExam($exam);
        $this->submitExam($exam, $attempt);

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempts.show', [$this->course, $exam, $attempt]))
            ->assertOk()
            ->assertSee('100.00%')
            ->assertSee('Review not available')
            ->assertDontSee('Question Breakdown')
            ->assertDontSee('Correct Answer:');
    }

    public function test_exam_page_hides_previous_attempt_scores_when_show_score_is_off(): void
    {
        $exam = $this->makeExam(['show_score' => false]);

        $attempt = $this->startExam($exam);
        $this->submitExam($exam, $attempt);

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.show', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('Score hidden')
            ->assertDontSee('100.00%');
    }
}
