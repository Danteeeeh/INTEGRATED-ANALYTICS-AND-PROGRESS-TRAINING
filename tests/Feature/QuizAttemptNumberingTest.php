<?php

namespace Tests\Feature;

use App\Http\Controllers\Student\QuizController;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Services\ContentProgressService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * Student → quiz → Start Attempt.
 *
 * `quiz_attempts` has a unique key on (quiz_id, student_id, attempt_number)
 * that includes soft-deleted rows. Attempt numbers were derived from a live
 * count, so a trashed attempt made the next insert reuse its slot and fail with
 * SQLSTATE[23000] "Duplicate entry ... for key 'quiz_attempt_unique'".
 */
class QuizAttemptNumberingTest extends TestCase
{
    use CreatesLmsUsers;

    /**
     * @return array<string, mixed>
     */
    private function scenario(): array
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser('student');

        $course = Course::factory()->create([
            'status' => 'published',
            'created_by' => $instructor->id,
        ]);

        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        $bank = QuestionBank::factory()->create([
            'course_id' => $course->id,
            'created_by' => $instructor->id,
        ]);

        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'What does CPU stand for?',
            'default_points' => 1,
            'created_by' => $instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        QuestionChoice::factory()->create([
            'question_id' => $question->id,
            'choice_text' => 'Central Processing Unit',
            'is_correct' => true,
            'position' => 1,
            'points' => 1,
        ]);

        $quiz = Quiz::create([
            'class_id' => $class->id,
            'title' => 'Attempt numbering quiz',
            'slug' => 'attempt-numbering-'.uniqid(),
            'status' => Quiz::STATUS_PUBLISHED,
            'created_by' => $instructor->id,
        ]);

        $quiz->questions()->attach($question->id, [
            'position' => 1,
            'points' => 1,
        ]);

        return compact('instructor', 'student', 'course', 'quiz');
    }

    public function test_a_soft_deleted_attempt_does_not_block_starting_the_next_one(): void
    {
        $s = $this->scenario();

        $trashed = QuizAttempt::create([
            'quiz_id' => $s['quiz']->id,
            'student_id' => $s['student']->id,
            'attempt_number' => 1,
            'started_at' => now()->subHour(),
            'status' => QuizAttempt::STATUS_SUBMITTED,
        ]);
        $trashed->delete();

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']]))
            ->assertOk();

        $attempt = QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(2, $attempt->attempt_number, 'The next attempt must skip the trashed slot.');
    }

    public function test_starting_the_same_quiz_twice_reuses_the_first_attempt(): void
    {
        $s = $this->scenario();

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']]))
            ->assertOk()
            ->assertSee('Attempt 1');

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']]))
            ->assertOk()
            ->assertSee('Attempt 1');

        $this->assertSame(1, QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->count(), 'A second Start must serve the open attempt, not insert another.');
    }

    /**
     * The real error: two requests both read "no attempt in progress" and both
     * insert attempt #1. The loser must recover from the unique-key collision
     * by serving the attempt that won, not by returning a 500.
     */
    public function test_a_duplicate_key_race_returns_the_attempt_that_won(): void
    {
        $s = $this->scenario();

        $winner = QuizAttempt::create([
            'quiz_id' => $s['quiz']->id,
            'student_id' => $s['student']->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
        ]);

        QuizAnswer::create([
            'quiz_attempt_id' => $winner->id,
            'question_id' => $s['quiz']->questions()->firstOrFail()->id,
        ]);

        $controller = new DuplicateRacingQuizController(app(ContentProgressService::class));
        $questions = $s['quiz']->questions()->with('choices')->get();

        $returned = $controller->openForTest($s['quiz'], $s['student']->id, $questions);

        $this->assertSame($winner->id, $returned->id, 'The loser must serve the winner\'s attempt.');
        $this->assertSame(1, QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->count(), 'The collision must not insert a second attempt.');
    }

    public function test_a_duplicate_key_that_leaves_no_winner_is_retried(): void
    {
        $s = $this->scenario();

        $controller = new DuplicateRacingQuizController(app(ContentProgressService::class));
        $questions = $s['quiz']->questions()->with('choices')->get();

        $attempt = $controller->openForTest($s['quiz'], $s['student']->id, $questions);

        $this->assertSame(1, $attempt->attempt_number);
        $this->assertTrue($attempt->answers()->exists(), 'The retry must still freeze the questions.');
        $this->assertSame(1, QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->count());
    }

    public function test_an_empty_in_progress_attempt_is_replaced_not_duplicated(): void
    {
        $s = $this->scenario();

        // The controller soft-deleted this row and then inserted attempt 1
        // again, which the unique index rejected.
        QuizAttempt::create([
            'quiz_id' => $s['quiz']->id,
            'student_id' => $s['student']->id,
            'attempt_number' => 1,
            'started_at' => now()->subMinutes(10),
            'status' => QuizAttempt::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']]))
            ->assertOk();

        $this->assertSame(1, QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->count());

        $this->assertSame(0, QuizAttempt::onlyTrashed()
            ->where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->count(), 'The unanswered attempt should have been force-deleted.');
    }
}

/**
 * Forces the losing half of the double-start race: the first attempt to insert
 * hits the unique key even though the pre-check saw no open attempt.
 */
class DuplicateRacingQuizController extends QuizController
{
    public bool $failNextCreation = true;

    protected function createAttempt(Quiz $quiz, int $studentId, Collection $questions): QuizAttempt
    {
        if ($this->failNextCreation) {
            $this->failNextCreation = false;

            throw new QueryException(
                'sqlite',
                'insert into "quiz_attempts" ("quiz_id", "student_id", "attempt_number") values (?, ?, ?)',
                [$quiz->id, $studentId, 1],
                new \PDOException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: quiz_attempts.quiz_id, quiz_attempts.student_id, quiz_attempts.attempt_number')
            );
        }

        return parent::createAttempt($quiz, $studentId, $questions);
    }

    public function openForTest(Quiz $quiz, int $studentId, Collection $questions): QuizAttempt
    {
        return $this->openAttempt($quiz, $studentId, $questions);
    }
}
