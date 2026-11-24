<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TEMPORARY verification for the timeout-grace fix (removed after the run).
 *
 * The attempt page auto-submits its answers when the clock hits zero. The
 * server used to grade from the database, where nothing had been saved yet, so
 * a timed-out attempt scored 0 even though the form carried the answers.
 */
class TempTimeoutGraceTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $student;

    private Course $course;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([\Database\Seeders\RoleSeeder::class, \Database\Seeders\PermissionSeeder::class]);

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->student = User::factory()->student()->create(['status' => 'active']);

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
    }

    private function question(): array
    {
        $bank = QuestionBank::create([
            'course_id' => $this->course->id,
            'title' => 'Bank '.uniqid(),
            'created_by' => $this->instructor->id,
            'status' => 'active',
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'What is 2 + 2?',
            'default_points' => 10,
            'created_by' => $this->instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        $wrong = QuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => '3',
            'is_correct' => false,
            'position' => 1,
            'points' => 0,
        ]);

        $right = QuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => '4',
            'is_correct' => true,
            'position' => 2,
            'points' => 0,
        ]);

        return [$question, $wrong, $right];
    }

    public function test_timed_out_quiz_keeps_the_answers_it_posts(): void
    {
        [$question, , $right] = $this->question();

        $quiz = Quiz::create([
            'class_id' => $this->class->id,
            'course_id' => $this->course->id,
            'title' => 'Timed quiz',
            'slug' => 'timed-'.uniqid(),
            'status' => Quiz::STATUS_PUBLISHED,
            'time_limit_minutes' => 20,
            'auto_submit_on_timeout' => true,
            'created_by' => $this->instructor->id,
        ]);

        $quiz->questions()->attach($question->id, ['position' => 1, 'points' => 10]);

        $this->actingAs($this->student)
            ->get(route('student.courses.quizzes.attempt.start', [$this->course, $quiz]))
            ->assertOk();

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $this->student->id)
            ->firstOrFail();

        // Deadline was 30 seconds ago: inside the grace window.
        $attempt->forceFill(['started_at' => now()->subMinutes(20)->subSeconds(30)])->save();

        $this->actingAs($this->student)
            ->post(route('student.courses.quizzes.attempt.store', [$this->course, $quiz]), [
                'answers' => [$question->id => $right->id],
            ])
            ->assertRedirect(route('student.courses.quizzes.attempts.show', [$this->course, $quiz, $attempt]));

        $attempt->refresh();

        $this->assertSame(QuizAttempt::STATUS_AUTO_SUBMITTED, $attempt->status, 'timed-out attempt should be marked auto-submitted');
        $this->assertGreaterThan(0, (float) $attempt->score, 'posted answers must be graded, not discarded');
        $this->assertSame(100.0, (float) $attempt->score_percent);
        $this->assertDatabaseHas('quiz_answers', [
            'quiz_attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'is_correct' => 1,
        ]);
    }

    public function test_timed_out_exam_keeps_the_answers_it_posts(): void
    {
        [$question, , $right] = $this->question();

        $exam = Exam::create([
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
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order' => 1,
            'points' => 10,
            'is_required' => true,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))
            ->assertOk();

        $attempt = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $this->student->id)
            ->firstOrFail();

        $attempt->forceFill(['started_at' => now()->subMinutes(20)->subSeconds(30)])->save();

        $this->actingAs($this->student)
            ->post(route('student.courses.exams.attempt.store', [$this->course, $exam]), [
                'answers' => [$question->id => $right->id],
            ])
            ->assertRedirect();

        $attempt->refresh();

        $this->assertGreaterThan(0, (float) $attempt->score, 'posted exam answers must be graded, not discarded');
        $this->assertSame(100.0, (float) $attempt->score_percent);
        $this->assertNotNull($attempt->submitted_at);
    }
}
