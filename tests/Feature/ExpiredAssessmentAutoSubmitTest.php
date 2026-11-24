<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamExtension;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizExtension;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * The scheduler must close attempts whose own clock expired while the student
 * was away. These tests exercise both assessments, the no-return case, the
 * guards (active, untimed, auto-submit disabled) and idempotency.
 */
class ExpiredAssessmentAutoSubmitTest extends TestCase
{
    use CreatesLmsUsers;
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private Course $course;

    private ClassModel $class;

    private Question $question;

    private QuestionChoice $rightChoice;

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
            'title' => 'Auto-submit bank',
            'created_by' => $this->instructor->id,
            'status' => 'active',
        ]);

        $this->question = Question::create([
            'question_bank_id' => $bank->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'What is 2 + 2?',
            'default_points' => 10,
            'created_by' => $this->instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        QuestionChoice::create([
            'question_id' => $this->question->id,
            'choice_text' => '3',
            'is_correct' => false,
            'position' => 1,
            'points' => 0,
        ]);

        $this->rightChoice = QuestionChoice::create([
            'question_id' => $this->question->id,
            'choice_text' => '4',
            'is_correct' => true,
            'position' => 2,
            'points' => 0,
        ]);
    }

    private function makeQuiz(array $overrides = []): Quiz
    {
        $quiz = Quiz::create(array_merge([
            'class_id' => $this->class->id,
            'course_id' => $this->course->id,
            'title' => 'Timed quiz',
            'slug' => 'timed-quiz-'.uniqid(),
            'status' => Quiz::STATUS_PUBLISHED,
            'time_limit_minutes' => 20,
            'auto_submit_on_timeout' => true,
            'created_by' => $this->instructor->id,
        ], $overrides));

        $quiz->questions()->attach($this->question->id, [
            'position' => 1,
            'points' => 10,
        ]);

        return $quiz;
    }

    private function startQuiz(Quiz $quiz): QuizAttempt
    {
        $this->actingAs($this->student)
            ->get(route('student.courses.quizzes.attempt.start', [$this->course, $quiz]))
            ->assertOk();

        return QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $this->student->id)
            ->firstOrFail();
    }

    private function makeExam(array $overrides = []): Exam
    {
        $exam = Exam::create(array_merge([
            'class_id' => $this->class->id,
            'course_id' => $this->course->id,
            'title' => 'Timed exam',
            'slug' => 'timed-exam-'.uniqid(),
            'exam_type' => Exam::TYPE_PRELIM,
            'duration_minutes' => 20,
            'auto_submit_on_timeout' => true,
            'total_points' => 10,
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

    private function startExam(Exam $exam): ExamAttempt
    {
        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))
            ->assertOk();

        return ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $this->student->id)
            ->firstOrFail();
    }

    public function test_the_auto_submit_command_is_registered_on_the_scheduler(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('assessments:auto-submit-expired')
            ->assertSuccessful();
    }

    public function test_command_auto_submits_an_expired_quiz_and_grades_stored_answers(): void
    {
        $quiz = $this->makeQuiz();
        $attempt = $this->startQuiz($quiz);

        QuizAnswer::where('quiz_attempt_id', $attempt->id)
            ->where('question_id', $this->question->id)
            ->firstOrFail()
            ->update(['answer_text' => (string) $this->rightChoice->id]);

        $attempt->forceFill(['started_at' => now()->subMinutes(25)])->save();

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 1 quiz and 0 exam attempt(s).')
            ->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_AUTO_SUBMITTED, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame(
            $attempt->started_at->copy()->addMinutes(20)->toDateTimeString(),
            $attempt->ended_at->toDateTimeString()
        );
        $this->assertSame(1200, $attempt->time_spent_seconds);
        $this->assertSame(100.0, (float) $attempt->score_percent);
        $this->assertSame(10.0, (float) $attempt->score);
        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $this->question->id,
            'is_correct' => 1,
        ]);
    }

    public function test_an_expired_quiz_with_no_answers_is_still_closed_with_zero(): void
    {
        $quiz = $this->makeQuiz();
        $attempt = $this->startQuiz($quiz);
        $attempt->forceFill(['started_at' => now()->subMinutes(25)])->save();

        $this->artisan('assessments:auto-submit-expired')->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_AUTO_SUBMITTED, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame(0.0, (float) $attempt->score_percent);
        $this->assertSame(0.0, (float) $attempt->score);
    }

    public function test_quiz_attempt_with_time_remaining_is_left_open(): void
    {
        $quiz = $this->makeQuiz();
        $attempt = $this->startQuiz($quiz);
        $attempt->forceFill(['started_at' => now()->subMinutes(5)])->save();

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 0 quiz and 0 exam attempt(s).')
            ->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_quiz_without_a_time_limit_is_left_open(): void
    {
        $quiz = $this->makeQuiz(['time_limit_minutes' => null]);
        $attempt = $this->startQuiz($quiz);
        $attempt->forceFill(['started_at' => now()->subDays(2)])->save();

        $this->artisan('assessments:auto-submit-expired')->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_quiz_with_auto_submit_disabled_is_left_open(): void
    {
        $quiz = $this->makeQuiz(['auto_submit_on_timeout' => false]);
        $attempt = $this->startQuiz($quiz);
        $attempt->forceFill(['started_at' => now()->subMinutes(25)])->save();

        $this->artisan('assessments:auto-submit-expired')->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_quiz_past_its_availability_deadline_is_closed_even_without_a_time_limit(): void
    {
        $quiz = $this->makeQuiz(['time_limit_minutes' => null]);
        $attempt = $this->startQuiz($quiz);

        QuizAnswer::where('quiz_attempt_id', $attempt->id)
            ->where('question_id', $this->question->id)
            ->firstOrFail()
            ->update(['answer_text' => (string) $this->rightChoice->id]);

        $quiz->update(['availability_until' => now()->subMinute()]);

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 1 quiz and 0 exam attempt(s).')
            ->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_AUTO_SUBMITTED, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertNotNull($attempt->ended_at);
        $this->assertSame(100.0, (float) $attempt->score_percent);
    }

    public function test_quiz_past_its_deadline_closes_when_auto_submit_on_timeout_is_disabled(): void
    {
        $quiz = $this->makeQuiz(['auto_submit_on_timeout' => false]);
        $attempt = $this->startQuiz($quiz);
        $attempt->forceFill(['started_at' => now()->subMinutes(5)])->save();

        $quiz->update(['availability_until' => now()->subMinute()]);

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 1 quiz and 0 exam attempt(s).')
            ->assertSuccessful();

        $this->assertSame(QuizAttempt::STATUS_AUTO_SUBMITTED, $attempt->fresh()->status);
    }

    public function test_a_quiz_extension_keeps_the_attempt_open_past_the_deadline(): void
    {
        $quiz = $this->makeQuiz(['time_limit_minutes' => null]);
        $attempt = $this->startQuiz($quiz);
        $quiz->update(['availability_until' => now()->subMinute()]);

        QuizExtension::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'extended_until' => now()->addDay(),
            'granted_by' => $this->instructor->id,
        ]);

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 0 quiz and 0 exam attempt(s).')
            ->assertSuccessful();

        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $attempt->fresh()->status);
    }

    public function test_command_auto_submits_an_expired_exam(): void
    {
        $exam = $this->makeExam();
        $attempt = $this->startExam($exam);

        ExamAnswer::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $this->question->id)
            ->firstOrFail()
            ->update(['answer_text' => (string) $this->rightChoice->id]);

        $attempt->forceFill(['started_at' => now()->subMinutes(25)])->save();

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 0 quiz and 1 exam attempt(s).')
            ->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(ExamAttempt::STATUS_GRADED, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertNotNull($attempt->ended_at);
        $this->assertSame(100.0, (float) $attempt->score_percent);
        $this->assertSame(10.0, (float) $attempt->score);
        $this->assertDatabaseHas('exam_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $this->question->id,
            'is_correct' => 1,
        ]);
    }

    public function test_exam_without_a_time_limit_is_left_open(): void
    {
        $exam = $this->makeExam(['duration_minutes' => 0]);
        $attempt = $this->startExam($exam);
        $attempt->forceFill(['started_at' => now()->subDays(2)])->save();

        $this->artisan('assessments:auto-submit-expired')->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(ExamAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_exam_with_auto_submit_disabled_is_left_open(): void
    {
        $exam = $this->makeExam(['auto_submit_on_timeout' => false]);
        $attempt = $this->startExam($exam);
        $attempt->forceFill(['started_at' => now()->subMinutes(25)])->save();

        $this->artisan('assessments:auto-submit-expired')->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(ExamAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_exam_past_its_deadline_closes_when_auto_submit_on_timeout_is_disabled(): void
    {
        $exam = $this->makeExam(['auto_submit_on_timeout' => false]);
        $attempt = $this->startExam($exam);
        $attempt->forceFill(['started_at' => now()->subMinutes(5)])->save();

        $exam->update(['ends_at' => now()->subMinute()]);

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 0 quiz and 1 exam attempt(s).')
            ->assertSuccessful();

        $this->assertTrue($attempt->fresh()->isCompleted());
    }

    public function test_exam_past_its_deadline_is_closed_even_without_a_duration(): void
    {
        $exam = $this->makeExam(['duration_minutes' => 0]);
        $attempt = $this->startExam($exam);

        ExamAnswer::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $this->question->id)
            ->firstOrFail()
            ->update(['answer_text' => (string) $this->rightChoice->id]);

        $exam->update(['ends_at' => now()->subMinute()]);

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 0 quiz and 1 exam attempt(s).')
            ->assertSuccessful();

        $attempt->refresh();

        $this->assertTrue($attempt->isCompleted());
        $this->assertNotSame(ExamAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame(100.0, (float) $attempt->score_percent);
    }

    public function test_an_exam_extension_keeps_the_attempt_open_past_the_deadline(): void
    {
        $exam = $this->makeExam(['duration_minutes' => 0]);
        $attempt = $this->startExam($exam);
        $exam->update(['ends_at' => now()->subMinute()]);

        ExamExtension::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'extended_until' => now()->addDay(),
            'granted_by' => $this->instructor->id,
        ]);

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 0 quiz and 0 exam attempt(s).')
            ->assertSuccessful();

        $this->assertSame(ExamAttempt::STATUS_IN_PROGRESS, $attempt->fresh()->status);
    }

    public function test_scheduler_waits_for_the_browser_timeout_grace(): void
    {
        $quiz = $this->makeQuiz();
        $quizAttempt = $this->startQuiz($quiz);

        $exam = $this->makeExam();
        $examAttempt = $this->startExam($exam);

        // One minute past the deadline: still inside the two-minute grace.
        $quizAttempt->forceFill(['started_at' => now()->subMinutes(21)])->save();
        $examAttempt->forceFill(['started_at' => now()->subMinutes(21)])->save();

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 0 quiz and 0 exam attempt(s).')
            ->assertSuccessful();

        $this->assertSame(QuizAttempt::STATUS_IN_PROGRESS, $quizAttempt->fresh()->status);
        $this->assertSame(ExamAttempt::STATUS_IN_PROGRESS, $examAttempt->fresh()->status);
    }

    public function test_a_late_browser_submission_uses_the_shared_auto_submit_path(): void
    {
        $quiz = $this->makeQuiz();
        $attempt = $this->startQuiz($quiz);

        QuizAnswer::where('quiz_attempt_id', $attempt->id)
            ->where('question_id', $this->question->id)
            ->firstOrFail()
            ->update(['answer_text' => (string) $this->rightChoice->id]);

        // Five minutes past the deadline: outside the two-minute grace window.
        $attempt->forceFill(['started_at' => now()->subMinutes(25)])->save();

        $this->actingAs($this->student)
            ->post(route('student.courses.quizzes.attempt.store', [$this->course, $quiz]), [
                'answers' => [],
            ])
            ->assertRedirect(route('student.courses.quizzes.attempts.show', [$this->course, $quiz, $attempt]));

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_AUTO_SUBMITTED, $attempt->status);
        $this->assertSame(100.0, (float) $attempt->score_percent);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_a_late_exam_submission_uses_the_shared_auto_submit_path(): void
    {
        $exam = $this->makeExam();
        $attempt = $this->startExam($exam);

        ExamAnswer::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $this->question->id)
            ->firstOrFail()
            ->update(['answer_text' => (string) $this->rightChoice->id]);

        $attempt->forceFill(['started_at' => now()->subMinutes(25)])->save();

        $this->actingAs($this->student)
            ->post(route('student.courses.exams.attempt.store', [$this->course, $exam]), [
                'answers' => [],
            ])
            ->assertRedirect(route('student.courses.exams.attempts.show', [$this->course, $exam, $attempt]));

        $attempt->refresh();

        $this->assertSame(ExamAttempt::STATUS_GRADED, $attempt->status);
        $this->assertSame(100.0, (float) $attempt->score_percent);
        $this->assertNotNull($attempt->submitted_at);
    }

    public function test_running_the_command_twice_does_not_regrade_a_closed_attempt(): void
    {
        $quiz = $this->makeQuiz();
        $attempt = $this->startQuiz($quiz);
        $attempt->forceFill(['started_at' => now()->subMinutes(25)])->save();

        $this->artisan('assessments:auto-submit-expired')->assertSuccessful();

        $attempt->refresh();
        $firstSubmittedAt = $attempt->submitted_at->toDateTimeString();

        $this->artisan('assessments:auto-submit-expired')
            ->expectsOutputToContain('Auto-submitted 0 quiz and 0 exam attempt(s).')
            ->assertSuccessful();

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_AUTO_SUBMITTED, $attempt->status);
        $this->assertSame($firstSubmittedAt, $attempt->submitted_at->toDateTimeString());
    }
}
