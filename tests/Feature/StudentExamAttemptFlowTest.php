<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Exam;
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
 * Clicking "Attempt" on a student's exam page returned 404.
 *
 * The controller has no abort(404) on that route, so the failure comes from
 * somewhere in the attempt path itself. This walks the whole journey — index,
 * show, confirm, start, answer, submit — so the real break is pinned down
 * rather than guessed at.
 */
class StudentExamAttemptFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    private ClassModel $class;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        \App\Models\Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);

        $this->exam = Exam::create([
            'class_id' => $this->class->id,
            'course_id' => $this->course->id,
            'title' => 'Prelim Exam',
            'slug' => 'prelim-'.uniqid(),
            'exam_type' => Exam::TYPE_PRELIM,
            'duration_minutes' => 60,
            'total_points' => 100,
            'status' => Exam::STATUS_PUBLISHED,
            'created_by' => $this->student->id,
        ]);

        $this->attachQuestion();

        $this->actingAs($this->student);
    }

    private function attachQuestion(): void
    {
        $bank = QuestionBank::create([
            'course_id' => $this->course->id,
            'title' => 'Bank',
            'created_by' => $this->student->id,
            'status' => 'active',
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'What is 2 + 2?',
            'default_points' => 10,
            'created_by' => $this->student->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        foreach (['3', '4'] as $i => $text) {
            QuestionChoice::create([
                'question_id' => $question->id,
                'choice_text' => $text,
                'is_correct' => $i === 1,
                'position' => $i + 1,
                'points' => 0,
            ]);
        }

        ExamQuestion::create([
            'exam_id' => $this->exam->id,
            'question_id' => $question->id,
            'order' => 1,
            'points' => 10,
            'is_required' => true,
        ]);
    }

    public function test_the_exam_index_reaches_the_exam(): void
    {
        $this->get(route('student.courses.exams.index', $this->course))->assertOk();
    }

    public function test_the_exam_show_page_reaches_the_attempt_link(): void
    {
        $this->get(route('student.courses.exams.show', [$this->course, $this->exam]))
            ->assertOk()
            ->assertSee(route('student.courses.exams.confirm', [$this->course, $this->exam]), false);
    }

    public function test_the_confirm_page_opens(): void
    {
        $this->get(route('student.courses.exams.confirm', [$this->course, $this->exam]))
            ->assertOk();
    }

    public function test_starting_an_attempt_opens_the_exam_rather_than_404(): void
    {
        $response = $this->get(
            route('student.courses.exams.attempt.start', [$this->course, $this->exam])
        );

        $this->assertNotSame(
            404,
            $response->getStatusCode(),
            'Attempt must not 404: '.$response->getStatusCode()
        );

        $response->assertOk();

        $this->assertSame(
            1,
            ExamAttempt::where('exam_id', $this->exam->id)
                ->where('student_id', $this->student->id)
                ->count(),
            'Starting an attempt must record it.'
        );
    }

    public function test_re_entering_while_in_progress_does_not_404(): void
    {
        $this->get(route('student.courses.exams.attempt.start', [$this->course, $this->exam]))->assertOk();

        $this->get(route('student.courses.exams.attempt.start', [$this->course, $this->exam]))->assertOk();

        $this->assertSame(1, ExamAttempt::where('exam_id', $this->exam->id)->count());
    }

    public function test_viewing_a_started_attempt_works(): void
    {
        $this->get(route('student.courses.exams.attempt.start', [$this->course, $this->exam]))->assertOk();

        $attempt = ExamAttempt::where('exam_id', $this->exam->id)->firstOrFail();

        $this->get(
            route('student.courses.exams.attempts.show', [$this->course, $this->exam, $attempt])
        )->assertOk();
    }

    /**
 * The button on the exam page is a form, not a link, so it POSTs.
 *
 * It has to reach a handler that *creates* the attempt. Posting to the attempt
 * path reaches the submit handler instead, which finds nothing in progress and
 * sends the student back to this same page — an endless loop rather than an
 * exam — so asserting "no 404" is not enough here.
 */
    public function test_the_start_exam_button_posts_to_a_handler_that_creates_the_attempt(): void
    {
        $confirm = $this->get(route('student.courses.exams.confirm', [$this->course, $this->exam]));

        $confirm->assertOk();

        // Read the form's action straight out of the rendered page.
        preg_match('/<form action="([^"]+)" method="POST">/', $confirm->getContent(), $m);

        $this->assertNotEmpty($m, 'The confirm page must render a POST form to start the exam.');

        $url = html_entity_decode($m[1]);

        $this->assertStringContainsString(
            'attempt/begin',
            $url,
            'Start must post to the handler that begins an attempt, not to the submit path.'
        );

        $this->assertSame(
            0,
            ExamAttempt::where('exam_id', $this->exam->id)->count(),
            'Nothing may be recorded before the button is pressed.'
        );

        $response = $this->post($url);

        $this->assertNotContains($response->getStatusCode(), [404, 405]);

        $this->assertSame(
            1,
            ExamAttempt::where('exam_id', $this->exam->id)
                ->where('student_id', $this->student->id)
                ->count(),
            'Pressing Start Exam must actually create the attempt.'
        );

        $this->assertSame(
            route('student.courses.exams.attempt.start', [$this->course, $this->exam]),
            $response->getTargetUrl(),
            'After starting, the student should land on the exam itself.'
        );
    }

    /**
     * A submit with no live attempt — stale tab, double click, lost session —
     * must not be a dead end.
     */
    public function test_submitting_without_a_live_attempt_returns_to_start_instead_of_404(): void
    {
        $response = $this->post(
            route('student.courses.exams.attempt.store', [$this->course, $this->exam]),
            ['answers' => []]
        );

        $this->assertNotSame(404, $response->getStatusCode());
        $this->assertSame(
            route('student.courses.exams.confirm', [$this->course, $this->exam]),
            $response->getTargetUrl()
        );
    }

    public function test_an_exam_with_no_questions_says_so_instead_of_failing(): void
    {
        $empty = Exam::create([
            'class_id' => $this->class->id,
            'course_id' => $this->course->id,
            'title' => 'Empty Exam',
            'slug' => 'empty-'.uniqid(),
            'exam_type' => Exam::TYPE_MIDTERM,
            'duration_minutes' => 30,
            'total_points' => 0,
            'status' => Exam::STATUS_PUBLISHED,
            'created_by' => $this->student->id,
        ]);

        $this->get(route('student.courses.exams.show', [$this->course, $empty]))->assertOk();

        $response = $this->get(route('student.courses.exams.attempt.start', [$this->course, $empty]));

        $this->assertNotSame(404, $response->getStatusCode());
    }
}