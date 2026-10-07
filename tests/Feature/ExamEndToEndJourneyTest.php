<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end: an instructor builds an exam with questions and answers, a
 * student sits it, and the answers are scored.
 *
 * Written as one journey rather than per-step tests, because the failure this
 * guards against is a seam between two features that each pass their own tests.
 */
class ExamEndToEndJourneyTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private Course $course;

    private ClassModel $class;

    private QuestionBank $bank;

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

        $this->course = Course::factory()->create(['created_by' => $this->instructor->id]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
        ]);

        $this->bank = QuestionBank::create([
            'course_id' => $this->course->id,
            'title' => 'Exam Bank',
            'created_by' => $this->instructor->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);
    }

    /** The instructor's whole authoring flow, exercised through real HTTP. */
    private function instructorBuildsExam(bool $publish = true): Exam
    {
        $this->actingAs($this->instructor);

        // 1. Create the exam.
        $this->post(route('instructor.courses.exams.store', $this->course), [
            'title' => 'Prelim Exam',
            'class_id' => $this->class->id,
            'exam_type' => Exam::TYPE_PRELIM,
            'duration_minutes' => 60,
            'passing_score_percent' => 60,
            'result_visibility' => Exam::VISIBILITY_AFTER_GRADING,
            'status' => Exam::STATUS_DRAFT,
        ])->assertSessionHasNoErrors();

        $exam = Exam::where('title', 'Prelim Exam')->firstOrFail();

        // 2. Add a question by hand, with its answer.
        $this->post(route('instructor.courses.exams.questions.store', [$this->course, $exam]), [
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'What is 2 + 2?',
            'points' => 10,
            'difficulty' => Question::DIFFICULTY_EASY,
            'choices' => [
                ['choice_text' => '3', 'is_correct' => false],
                ['choice_text' => '4', 'is_correct' => true],
                ['choice_text' => '5', 'is_correct' => false],
            ],
        ])->assertSessionHasNoErrors();

        // 3. Add a second question from the bank, with its answer.
        $banked = Question::create([
            'question_bank_id' => $this->bank->id,
            'question_type' => Question::TYPE_TRUE_FALSE,
            'question_text' => 'The Earth is round.',
            'default_points' => 20,
            'created_by' => $this->instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        QuestionChoice::create([
            'question_id' => $banked->id,
            'choice_text' => 'True',
            'is_correct' => true,
            'position' => 1,
            'points' => 0,
        ]);

        QuestionChoice::create([
            'question_id' => $banked->id,
            'choice_text' => 'False',
            'is_correct' => false,
            'position' => 2,
            'points' => 0,
        ]);

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$banked->id], 'points' => [$banked->id => 20]]
        )->assertRedirect();

        // 4. The edit page shows both, with their points.
        $this->get(route('instructor.courses.exams.edit', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('What is 2 + 2?')
            ->assertSee('The Earth is round.');

        // 5. Publish it.
        if ($publish) {
            $this->post(route('instructor.courses.exams.publish', [$this->course, $exam]))
                ->assertRedirect();
        }

        return $exam->fresh();
    }

    public function test_the_whole_journey_works_and_scores_correctly(): void
    {
        $exam = $this->instructorBuildsExam();

        $this->assertSame(Exam::STATUS_PUBLISHED, $exam->status);
        $this->assertSame(2, ExamQuestion::where('exam_id', $exam->id)->count());
        $this->assertSame(30.0, $exam->getTotalPoints());

        // ── The student sits it ────────────────────────────────────
        $this->actingAs($this->student);

        $this->get(route('student.courses.exams.index', $this->course))
            ->assertOk()
            ->assertSee('Prelim Exam')
            ->assertSee('2 questions', false);

        // The exam page offers a start.
        $this->get(route('student.courses.exams.show', [$this->course, $exam]))
            ->assertOk()
            ->assertSee(route('student.courses.exams.confirm', [$this->course, $exam]), false);

        // Confirm, then start.
        $this->get(route('student.courses.exams.confirm', [$this->course, $exam]))->assertOk();

        $this->post(route('student.courses.exams.attempt.begin', [$this->course, $exam]))
            ->assertRedirect(route('student.courses.exams.attempt.start', [$this->course, $exam]));

        $attemptPage = $this->get(route('student.courses.exams.attempt.start', [$this->course, $exam]));
        $attemptPage->assertOk();
        $attemptPage->assertSee('What is 2 + 2?');

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $this->student->id)
            ->firstOrFail();

        $this->assertSame(ExamAttempt::STATUS_IN_PROGRESS, $attempt->status);

        // ── Answer both questions correctly ────────────────────────
        // Posted exactly as the attempt form does: answers[<question_id>] is a
        // bare choice id for single-answer types, a list for multiple answer,
        // and free text for the rest.
        $mc = Question::where('question_text', 'What is 2 + 2?')->firstOrFail();
        $correctMc = $mc->choices()->where('is_correct', true)->firstOrFail();

        $tf = Question::where('question_text', 'The Earth is round.')->firstOrFail();
        $correctTf = $tf->choices()->where('is_correct', true)->firstOrFail();

        $this->post(route('student.courses.exams.attempt.store', [$this->course, $exam]), [
            'answers' => [
                $mc->id => $correctMc->id,
                $tf->id => $correctTf->id,
            ],
        ])->assertRedirect();

        // ── The result ─────────────────────────────────────────────
        $attempt = $attempt->fresh();

        $this->assertNotSame(
            ExamAttempt::STATUS_IN_PROGRESS,
            $attempt->status,
            'Submitting must end the attempt.'
        );

        // ExamAnswer keys on exam_attempt_id (QuizAnswer uses attempt_id).
        $this->assertSame(2, ExamAnswer::where('exam_attempt_id', $attempt->id)->count());

        // Both answered correctly, so a full score.
        $this->assertEqualsWithDelta(100.0, (float) $attempt->score_percent, 0.01);
        $this->assertTrue((bool) $attempt->is_passed);

        // The student can review it.
        $this->get(route('student.courses.exams.attempts.show', [$this->course, $exam, $attempt]))
            ->assertOk();
    }

    public function test_wrong_answers_score_below_full_marks(): void
    {
        $exam = $this->instructorBuildsExam();

        $this->actingAs($this->student);

        $this->post(route('student.courses.exams.attempt.begin', [$this->course, $exam]));

        $attempt = ExamAttempt::where('exam_id', $exam->id)->firstOrFail();

        $mc = Question::where('question_text', 'What is 2 + 2?')->firstOrFail();
        $wrong = $mc->choices()->where('is_correct', false)->firstOrFail();

        $tf = Question::where('question_text', 'The Earth is round.')->firstOrFail();
        $wrongTf = $tf->choices()->where('is_correct', false)->firstOrFail();

        $this->post(route('student.courses.exams.attempt.store', [$this->course, $exam]), [
            'answers' => [
                $mc->id => $wrong->id,
                $tf->id => $wrongTf->id,
            ],
        ]);

        $this->assertEqualsWithDelta(0.0, (float) $attempt->fresh()->score_percent, 0.01);
    }

    public function test_questions_lock_once_published(): void
    {
        $exam = $this->instructorBuildsExam();

        $this->actingAs($this->instructor);

        // A published paper must not be quietly edited underneath students.
        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$this->question()->id]]
        )->assertStatus(422);

        $this->assertSame(2, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    public function test_the_paper_is_frozen_onto_the_attempt(): void
    {
        $exam = $this->instructorBuildsExam();

        $this->actingAs($this->student);

        $this->post(route('student.courses.exams.attempt.begin', [$this->course, $exam]));

        $mcId = Question::where('question_text', 'What is 2 + 2?')->value('id');

        // Rewording the bank question afterwards must not rewrite what the
        // student is being asked, or the mark already earned against it.
        Question::where('id', $mcId)
            ->update(['question_text' => 'What is 2 + 3?']);

        $attempt = ExamAttempt::where('exam_id', $exam->id)->firstOrFail();

        $this->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('What is 2 + 2?')
            ->assertDontSee('What is 2 + 3?');

        $frozen = ExamAnswer::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $mcId)
            ->firstOrFail();

        $this->assertStringContainsString(
            'What is 2 + 2?',
            $frozen->renderedQuestionText()
        );
    }

    public function test_an_essay_waits_for_a_human_rather_than_scoring_zero(): void
    {
        // Left as a draft, so the essay can still be attached: a published paper
        // is deliberately locked.
        $exam = $this->instructorBuildsExam(publish: false);

        $essay = Question::create([
            'question_bank_id' => $this->bank->id,
            'question_type' => Question::TYPE_ESSAY,
            'question_text' => 'Explain the difference between a class and an object.',
            'default_points' => 30,
            'created_by' => $this->instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->instructor);
        $this->post(route('instructor.courses.exams.questions.attach', [$this->course, $exam]), [
            'question_ids' => [$essay->id],
            'points' => [$essay->id => 30],
        ])->assertRedirect();

        $this->post(route('instructor.courses.exams.publish', [$this->course, $exam]))
            ->assertRedirect();

        $this->actingAs($this->student);
        $this->post(route('student.courses.exams.attempt.begin', [$this->course, $exam]));

        $mc = Question::where('question_text', 'What is 2 + 2?')->firstOrFail();
        $tf = Question::where('question_text', 'The Earth is round.')->firstOrFail();

        $this->post(route('student.courses.exams.attempt.store', [$this->course, $exam]), [
            'answers' => [
                $mc->id => $mc->choices()->where('is_correct', true)->value('id'),
                $tf->id => $tf->choices()->where('is_correct', true)->value('id'),
                $essay->id => 'An object is an instance of a class.',
            ],
        ])->assertRedirect();

        $attempt = ExamAttempt::where('exam_id', $exam->id)->firstOrFail();

        // The two objective questions are marked; the essay is not silently lost
        // as a wrong answer, and the attempt is not declared finally graded.
        $this->assertSame(ExamAttempt::STATUS_SUBMITTED, $attempt->status);
        $this->assertNull($attempt->is_passed, 'An unmarked essay cannot settle a pass or fail.');
        $this->assertTrue((bool) $attempt->flagged_for_review);

        $this->assertEqualsWithDelta(
            30.0,
            (float) $attempt->earned_points,
            0.01,
            'The two answered questions are worth 30 points between them.'
        );

        $essayAnswer = ExamAnswer::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $essay->id)
            ->firstOrFail();

        $this->assertNull($essayAnswer->is_correct, 'Pending marking, not marked wrong.');
        $this->assertEqualsWithDelta(0.0, (float) $essayAnswer->points_awarded, 0.01);
        $this->assertSame(
            'An object is an instance of a class.',
            $essayAnswer->answer_text,
            'The student\'s own words must be kept for whoever marks it.'
        );
    }

    public function test_an_unanswered_question_does_not_earn_credit(): void
    {
        $exam = $this->instructorBuildsExam();

        $this->actingAs($this->student);

        $this->post(route('student.courses.exams.attempt.begin', [$this->course, $exam]));

        // Answer only the 10-point question, leave the 20-point one blank.
        $mc = Question::where('question_text', 'What is 2 + 2?')->firstOrFail();

        $this->post(route('student.courses.exams.attempt.store', [$this->course, $exam]), [
            'answers' => [
                $mc->id => $mc->choices()->where('is_correct', true)->value('id'),
            ],
        ])->assertRedirect();

        $attempt = ExamAttempt::where('exam_id', $exam->id)->firstOrFail();

        // 10 of 30 — half marks, not a pass and not a zero.
        $this->assertEqualsWithDelta(33.33, (float) $attempt->score_percent, 0.01);
        $this->assertFalse((bool) $attempt->is_passed);
    }

    private function question(): Question
    {
        $question = Question::create([
            'question_bank_id' => $this->bank->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'A late addition',
            'default_points' => 5,
            'created_by' => $this->instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        QuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'Answer',
            'is_correct' => true,
            'position' => 1,
            'points' => 0,
        ]);

        return $question;
    }
}