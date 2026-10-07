<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Exam;
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
 * An exam could be created but there was nowhere to put questions in it: the
 * Questions panel said "attach from your question bank or add them manually"
 * and offered neither.
 *
 * These cover the four things an instructor needs: pick from the bank, write a
 * question inline, set how many points each is worth, and take one back out.
 */
class InstructorExamQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

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

        $this->course = Course::factory()->create(['created_by' => $this->instructor->id]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
        ]);

        $this->bank = QuestionBank::create([
            'course_id' => $this->course->id,
            'title' => 'My Bank',
            'created_by' => $this->instructor->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->instructor);
    }

    private function exam(array $overrides = []): Exam
    {
        return Exam::create(array_merge([
            'class_id' => $this->class->id,
            'course_id' => $this->course->id,
            'title' => 'Prelim Exam',
            'slug' => 'prelim-'.uniqid(),
            'exam_type' => Exam::TYPE_PRELIM,
            'created_by' => $this->instructor->id,
            'status' => Exam::STATUS_DRAFT,
        ], $overrides));
    }

    private function question(?QuestionBank $bank = null, float $points = 1): Question
    {
        $question = Question::create([
            'question_bank_id' => ($bank ?? $this->bank)->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'Bank question',
            'default_points' => $points,
            'created_by' => $this->instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        foreach (['A', 'B', 'C', 'D'] as $i => $text) {
            QuestionChoice::create([
                'question_id' => $question->id,
                'choice_text' => $text,
                'is_correct' => $i === 0,
                'position' => $i + 1,
                'points' => 0,
            ]);
        }

        return $question;
    }

    // ── Picking from the bank ─────────────────────────────────────

    public function test_questions_can_be_attached_from_the_bank(): void
    {
        $exam = $this->exam();
        $one = $this->question($this->bank, 2);
        $two = $this->question($this->bank, 5);

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$one->id, $two->id], 'points' => [$one->id => 2, $two->id => 5]]
        )->assertRedirect();

        $this->assertSame(2, ExamQuestion::where('exam_id', $exam->id)->count());
        $this->assertSame(2, $exam->fresh()->questions->count());

        $points = $exam->fresh()->questions->mapWithKeys(
            fn ($q) => [$q->id => (float) $q->pivot->points]
        )->all();

        $this->assertSame(2.0, $points[$one->id]);
        $this->assertSame(5.0, $points[$two->id]);
    }

    public function test_attached_questions_are_numbered_in_order(): void
    {
        $exam = $this->exam();
        $questions = collect(range(1, 3))->map(fn () => $this->question())->all();

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => array_column($questions, 'id')]
        )->assertRedirect();

        $orders = ExamQuestion::where('exam_id', $exam->id)
            ->orderBy('question_id')
            ->pluck('order')
            ->all();

        $this->assertSame([1, 2, 3], $orders);
    }

    public function test_points_default_to_the_banks_own_value(): void
    {
        $exam = $this->exam();
        $question = $this->question($this->bank, 7);

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$question->id]]
        )->assertRedirect();

        $this->assertSame(7.0, (float) ExamQuestion::where('exam_id', $exam->id)->first()->points);
    }

    public function test_attaching_the_same_question_twice_does_not_duplicate_it(): void
    {
        $exam = $this->exam();
        $question = $this->question();

        $url = route('instructor.courses.exams.questions.attach', [$this->course, $exam]);

        $this->post($url, ['question_ids' => [$question->id]])->assertRedirect();
        $this->post($url, ['question_ids' => [$question->id]])->assertRedirect();

        $this->assertSame(1, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    public function test_a_question_from_another_instructors_bank_cannot_be_attached(): void
    {
        $exam = $this->exam();

        $other = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $otherBank = QuestionBank::create([
            'title' => 'Their Bank',
            'created_by' => $other->id,
            'status' => 'active',
        ]);

        $foreign = $this->question($otherBank);

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$foreign->id]]
        );

        $this->assertSame(
            0,
            ExamQuestion::where('exam_id', $exam->id)->count(),
            'A question from a bank the instructor cannot see must not be linked.'
        );
    }

    public function test_an_admin_shared_bank_can_be_used(): void
    {
        $exam = $this->exam();

        $shared = QuestionBank::create([
            'title' => 'Shared With Everyone',
            'created_by' => $this->instructor->id,
            'is_shared' => true,
            'status' => 'active',
        ]);

        $question = $this->question($shared);

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$question->id]]
        )->assertRedirect();

        $this->assertSame(1, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    // ── Writing a question inline ─────────────────────────────────

    public function test_a_question_can_be_added_manually(): void
    {
        $exam = $this->exam();

        $this->post(
            route('instructor.courses.exams.questions.store', [$this->course, $exam]),
            [
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'What is 2 + 2?',
                'difficulty' => Question::DIFFICULTY_EASY,
                'default_points' => 2,
                'points' => 2,
                'choices' => [
                    ['choice_text' => '3', 'is_correct' => false],
                    ['choice_text' => '4', 'is_correct' => true],
                ],
            ]
        )->assertRedirect();

        $question = Question::where('question_text', 'What is 2 + 2?')->firstOrFail();

        $this->assertSame($this->instructor->id, $question->created_by);
        $this->assertSame(2, $question->choices()->count());
        $this->assertTrue($question->choices()->where('is_correct', true)->exists());
        $this->assertSame(1, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    public function test_a_multiple_choice_question_needs_choices_and_a_correct_answer(): void
    {
        $exam = $this->exam();

        // No choices at all.
        $this->post(
            route('instructor.courses.exams.questions.store', [$this->course, $exam]),
            [
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'No choices',
                'points' => 1,
            ]
        )->assertSessionHasErrors('choices');

        // Choices but nothing marked correct.
        $this->post(
            route('instructor.courses.exams.questions.store', [$this->course, $exam]),
            [
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'No correct answer',
                'points' => 1,
                'choices' => [
                    ['choice_text' => 'A'],
                    ['choice_text' => 'B'],
                ],
            ]
        )->assertSessionHasErrors('choices');

        $this->assertSame(0, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    public function test_an_essay_question_needs_no_choices(): void
    {
        $exam = $this->exam();

        $this->post(
            route('instructor.courses.exams.questions.store', [$this->course, $exam]),
            [
                'question_type' => Question::TYPE_ESSAY,
                'question_text' => 'Explain recursion.',
                'points' => 10,
            ]
        )->assertRedirect();

        $this->assertSame(1, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    // ── Points, order and removal ──────────────────────────────────

    public function test_a_questions_points_can_be_changed(): void
    {
        $exam = $this->exam();
        $question = $this->question();

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$question->id]]
        );

        $this->put(
            route('instructor.courses.exams.questions.update', [$this->course, $exam, $question]),
            ['points' => 25]
        )->assertRedirect();

        $this->assertSame(25.0, (float) ExamQuestion::where('exam_id', $exam->id)->first()->points);
        $this->assertSame(25.0, $exam->fresh()->getTotalPoints());
    }

    public function test_a_question_can_be_removed_from_the_exam_without_losing_the_bank_copy(): void
    {
        $exam = $this->exam();
        $question = $this->question();

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$question->id]]
        );

        $this->delete(
            route('instructor.courses.exams.questions.destroy', [$this->course, $exam, $question])
        )->assertRedirect();

        $this->assertSame(0, ExamQuestion::where('exam_id', $exam->id)->count());
        $this->assertNotNull(
            Question::find($question->id),
            'Removing it from one exam must not delete it from the bank.'
        );
    }

    public function test_reordering_renumbers_the_exam_consecutively(): void
    {
        $exam = $this->exam();
        $questions = collect(range(1, 3))->map(fn () => $this->question())->all();

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => array_column($questions, 'id')]
        );

        $reversed = array_reverse(array_column($questions, 'id'));

        $this->post(
            route('instructor.courses.exams.questions.reorder', [$this->course, $exam]),
            ['order' => $reversed]
        )->assertRedirect();

        $actual = ExamQuestion::where('exam_id', $exam->id)
            ->orderBy('order')
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertSame($reversed, $actual);
        $this->assertSame([1, 2, 3], ExamQuestion::where('exam_id', $exam->id)->orderBy('order')->pluck('order')->all());
    }

    // ── Guard rails ───────────────────────────────────────────────

    public function test_questions_cannot_be_managed_on_another_instructors_exam(): void
    {
        $other = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $theirCourse = Course::factory()->create(['created_by' => $other->id]);
        $theirClass = ClassModel::factory()->create([
            'course_id' => $theirCourse->id,
            'instructor_id' => $other->id,
        ]);

        $theirExam = $this->exam(['class_id' => $theirClass->id, 'course_id' => $theirCourse->id]);
        $question = $this->question();

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $theirExam]),
            ['question_ids' => [$question->id]]
        )->assertForbidden();
    }

    public function test_questions_cannot_be_changed_once_the_exam_is_published(): void
    {
        $exam = $this->exam(['status' => Exam::STATUS_PUBLISHED]);
        $question = $this->question();

        $this->post(
            route('instructor.courses.exams.questions.attach', [$this->course, $exam]),
            ['question_ids' => [$question->id]]
        )->assertStatus(422);

        $this->assertSame(0, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    public function test_the_question_picker_page_lists_the_instructors_bank_questions(): void
    {
        $exam = $this->exam();
        $mine = $this->question($this->bank);

        $other = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $theirBank = QuestionBank::create([
            'title' => 'Not Mine',
            'created_by' => $other->id,
            'status' => 'active',
        ]);

        $this->question($theirBank);

        $response = $this->get(route('instructor.courses.exams.questions.index', [$this->course, $exam]));

        $response->assertOk();
        $response->assertSee(e($mine->question_text));

        $ids = collect($response->viewData('questions'))->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertCount(1, $ids, 'Only questions this instructor may use are offered.');
    }

    public function test_the_exam_page_offers_a_way_to_add_questions(): void
    {
        $exam = $this->exam();

        $this->get(route('instructor.courses.exams.show', [$this->course, $exam]))
            ->assertOk()
            ->assertSee(route('instructor.courses.exams.questions.index', [$this->course, $exam]), false);
    }
}