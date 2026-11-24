<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * The quiz attempt page must resume its clock after a refresh and must not
 * discard the localStorage safety copy until the results page proves the
 * submission reached the server.
 */
class QuizAttemptResumeTest extends TestCase
{
    use CreatesLmsUsers;
    use RefreshDatabase;

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

        $choice = QuestionChoice::factory()->create([
            'question_id' => $question->id,
            'choice_text' => 'Central Processing Unit',
            'is_correct' => true,
            'position' => 1,
            'points' => 1,
        ]);

        $quiz = Quiz::create([
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Resume quiz',
            'slug' => 'resume-'.uniqid(),
            'status' => Quiz::STATUS_PUBLISHED,
            'time_limit_minutes' => 30,
            'auto_submit_on_timeout' => true,
            'created_by' => $instructor->id,
        ]);

        $quiz->questions()->attach($question->id, [
            'position' => 1,
            'points' => 1,
        ]);

        return compact('instructor', 'student', 'course', 'class', 'quiz', 'question', 'choice');
    }

    public function test_reloading_an_attempt_resumes_the_server_computed_clock(): void
    {
        $s = $this->scenario();

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']]))
            ->assertOk();

        $attempt = QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->firstOrFail();

        // Ten minutes of the thirty-minute limit are already gone.
        $attempt->forceFill(['started_at' => now()->subMinutes(10)])->save();

        $response = $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']]))
            ->assertOk();

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/let timeRemaining = (\d+);/', $html);
        preg_match('/let timeRemaining = (\d+);/', $html, $matches);
        $remaining = (int) ($matches[1] ?? 0);

        $this->assertGreaterThanOrEqual(1190, $remaining);
        $this->assertLessThanOrEqual(1200, $remaining);
        $response->assertSee("const storageKey = 'quizAnswers_' + attemptId;", false);
        $response->assertSee('syncAnsweredQuestions();', false);
        $response->assertDontSee('markAnswered(', false);
        $response->assertDontSee("localStorage.removeItem('quizAnswers_", false);
    }

    public function test_results_page_clears_the_saved_quiz_answers_after_submission(): void
    {
        $s = $this->scenario();

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']]))
            ->assertOk();

        $attempt = QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->firstOrFail();

        $this->actingAs($s['student'])
            ->post(route('student.courses.quizzes.attempt.store', [$s['course'], $s['quiz']]), [
                'answers' => [$s['question']->id => $s['choice']->id],
            ])
            ->assertRedirect(route('student.courses.quizzes.attempts.show', [$s['course'], $s['quiz'], $attempt]));

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempts.show', [$s['course'], $s['quiz'], $attempt]))
            ->assertOk()
            ->assertSee("localStorage.removeItem('quizAnswers_{$attempt->id}')", false);
    }
}
