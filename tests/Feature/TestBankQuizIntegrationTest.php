<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\Quiz;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * §8 — "Add Questions from Test Bank" during quiz creation.
 *
 * The point of the section is that a picked question is *linked*, not copied,
 * and that saving an edit no longer rewrites every question in the quiz. The
 * old flow detached the whole pivot table and re-created each question, which
 * both duplicated the bank and orphaned the rows past attempts were graded
 * against.
 *
 * §9 quota selection has its own file (TestBankQuotaTest).
 */
class TestBankQuizIntegrationTest extends TestCase
{
    use CreatesLmsUsers;

    /**
     * A course whose classes the given instructor teaches, so both the
     * course-level `isManagedBy()` gate and the class ownership check pass.
     *
     * @return array{instructor: User, course: Course, class: ClassModel}
     */
    private function teachingSetup(?User $instructor = null): array
    {
        $instructor ??= $this->makeUser('instructor');

        $course = Course::factory()->create();
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
        ]);

        return ['instructor' => $instructor, 'course' => $course, 'class' => $class];
    }

    /**
     * A bank and question owned by the given instructor (a fresh one when not
     * supplied), so the picker's ownership rule is exercised against the same
     * person who will actually submit the quiz form.
     *
     * @return array{instructor: User, bank: QuestionBank, question: Question}
     */
    private function reusableQuestion(?User $instructor = null, array $question = []): array
    {
        $instructor ??= $this->makeUser('instructor');

        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);
        $question = Question::factory()->create(array_merge([
            'question_bank_id' => $bank->id,
            'created_by' => $instructor->id,
            'question_text' => 'What does CPU stand for?',
        ], $question));

        return ['instructor' => $instructor, 'bank' => $bank, 'question' => $question];
    }

    private function makeQuiz(Course $course, ClassModel $class, User $instructor, string $title = 'Existing Quiz'): Quiz
    {
        return Quiz::create([
            'class_id' => $class->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'status' => 'draft',
            'created_by' => $instructor->id,
        ]);
    }

    public function test_quiz_create_form_offers_reusable_questions_from_the_test_bank(): void
    {
        $setup = $this->teachingSetup();
        $instructor = $setup['instructor'];
        $course = $setup['course'];

        $mine = Question::factory()->create([
            'question_bank_id' => QuestionBank::factory()->create(['created_by' => $instructor->id]),
            'created_by' => $instructor->id,
            'question_text' => 'Reusable question of mine',
        ]);

        $someoneElses = Question::factory()->create([
            'question_bank_id' => QuestionBank::factory()->create(['created_by' => $this->makeUser('instructor')->id]),
            'question_text' => 'Private question of another instructor',
        ]);

        $response = $this->actingAs($instructor)
            ->get(route('instructor.courses.quizzes.create', $course));

        $response->assertOk()
            ->assertSee('From the Test Bank')
            ->assertSee('Reusable question of mine')
            ->assertDontSee('Private question of another instructor')
            ->assertSee('name="test_bank_question_ids[]"', false);

        // The points input only exists inside the picker, so it proves the
        // foreign question was left out of the list rather than merely
        // filtered from the visible text.
        $content = $response->getContent();

        $this->assertStringContainsString('name="test_bank_points['.$mine->id.']"', $content);
        $this->assertStringNotContainsString('name="test_bank_points['.$someoneElses->id.']"', $content);
    }

    public function test_picking_a_test_bank_question_links_it_without_creating_a_copy(): void
    {
        $setup = $this->teachingSetup();
        $instructor = $setup['instructor'];
        $course = $setup['course'];
        $class = $setup['class'];

        $reusable = $this->reusableQuestion($instructor);
        $question = $reusable['question'];

        $totalBefore = Question::count();

        $this->actingAs($instructor)
            ->post(route('instructor.courses.quizzes.store', $course), [
                'class_id' => $class->id,
                'title' => 'Banked Quiz',
                'status' => 'draft',
                'test_bank_question_ids' => [$question->id],
                'test_bank_points' => [$question->id => 4],
            ])
            ->assertRedirect(route('instructor.courses.quizzes.index', $course));

        $quiz = Quiz::where('title', 'Banked Quiz')->firstOrFail();

        $this->assertSame(
            [$question->id],
            $quiz->questions()->pluck('questions.id')->all(),
            'The picked question should be linked by id, not duplicated.'
        );

        $this->assertSame(4, (int) $quiz->questions()->value('quiz_questions.points'));
        $this->assertSame($totalBefore, Question::count(), 'A copy of the question was created.');
    }

    public function test_picking_a_question_from_a_foreign_private_bank_is_refused(): void
    {
        $setup = $this->teachingSetup();
        $instructor = $setup['instructor'];
        $course = $setup['course'];
        $class = $setup['class'];

        $foreign = Question::factory()->create([
            'question_bank_id' => QuestionBank::factory()->create([
                'created_by' => $this->makeUser('instructor')->id,
                'is_shared' => false,
            ]),
            'question_text' => 'Someone else private question',
        ]);

        $this->actingAs($instructor)
            ->post(route('instructor.courses.quizzes.store', $course), [
                'class_id' => $class->id,
                'title' => 'Sneaky Quiz',
                'status' => 'draft',
                'test_bank_question_ids' => [$foreign->id],
            ])
            ->assertRedirect(route('instructor.courses.quizzes.index', $course));

        $quiz = Quiz::where('title', 'Sneaky Quiz')->firstOrFail();

        $this->assertSame(
            [],
            $quiz->questions()->pluck('questions.id')->all(),
            'A posted id must still be resolved through the Test Bank ownership rule.'
        );
    }

    public function test_editing_a_quiz_updates_the_original_question_instead_of_cloning_it(): void
    {
        $setup = $this->teachingSetup();
        $instructor = $setup['instructor'];
        $course = $setup['course'];
        $class = $setup['class'];

        $reusable = $this->reusableQuestion($instructor);
        $question = $reusable['question'];

        $quiz = $this->makeQuiz($course, $class, $instructor);
        $quiz->questions()->attach($question->id, ['position' => 1, 'points' => 2]);

        $totalBefore = Question::count();

        $this->actingAs($instructor)
            ->put(route('instructor.courses.quizzes.update', [$course, $quiz]), [
                'class_id' => $class->id,
                'title' => 'Renamed Quiz',
                'status' => 'draft',
                'inline_questions_managed' => '1',
                'questions' => [
                    1 => [
                        'question_id' => $question->id,
                        'text' => 'Reworded question text',
                        'points' => 3,
                        'choices' => [1 => 'Alpha', 2 => 'Beta', 3 => 'Gamma', 4 => 'Delta'],
                        'correct_choice' => 2,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame($totalBefore, Question::count(), 'Editing a quiz cloned one of its questions.');

        $question->refresh();
        $this->assertSame('Reworded question text', $question->question_text);
        $this->assertSame($reusable['bank']->id, $question->question_bank_id, 'The question should stay in its bank.');

        $this->assertSame([$question->id], $quiz->questions()->pluck('questions.id')->all());
        $this->assertSame(3, (int) $quiz->questions()->value('quiz_questions.points'));

        $correct = $question->choices()->where('is_correct', true)->first();
        $this->assertSame('Beta', $correct?->choice_text, 'The marked answer key landed on the wrong choice.');
    }

    public function test_removing_an_inline_question_unlinks_it_without_deleting_the_row(): void
    {
        $setup = $this->teachingSetup();
        $instructor = $setup['instructor'];
        $course = $setup['course'];
        $class = $setup['class'];

        $reusable = $this->reusableQuestion($instructor);
        $question = $reusable['question'];

        $quiz = $this->makeQuiz($course, $class, $instructor);
        $quiz->questions()->attach($question->id, ['position' => 1, 'points' => 1]);

        $this->actingAs($instructor)
            ->put(route('instructor.courses.quizzes.update', [$course, $quiz]), [
                'class_id' => $class->id,
                'title' => $quiz->title,
                'status' => 'draft',
                // The form says it owns the whole list, and the list is now empty.
                'inline_questions_managed' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(0, $quiz->questions()->count(), 'The removed row should be unlinked.');
        $this->assertNotNull(
            Question::find($question->id),
            'Unlinking must not delete the question itself.'
        );
    }

    public function test_a_request_that_never_mentions_inline_questions_leaves_them_alone(): void
    {
        $setup = $this->teachingSetup();
        $instructor = $setup['instructor'];
        $course = $setup['course'];
        $class = $setup['class'];

        $reusable = $this->reusableQuestion($instructor);
        $existing = $reusable['question'];

        $extra = Question::factory()->create([
            'question_bank_id' => $existing->question_bank_id,
            'created_by' => $instructor->id,
            'question_text' => 'Second reusable question',
        ]);

        $quiz = $this->makeQuiz($course, $class, $instructor);
        $quiz->questions()->attach($existing->id, ['position' => 1, 'points' => 7]);

        $this->actingAs($instructor)
            ->put(route('instructor.courses.quizzes.update', [$course, $quiz]), [
                'class_id' => $class->id,
                'title' => $quiz->title,
                'status' => 'draft',
                // No inline_questions_managed, no questions[] — only a pick.
                'test_bank_question_ids' => [$extra->id],
                'test_bank_points' => [$extra->id => 2],
            ])
            ->assertRedirect();

        $links = $quiz->questions()->get();

        $this->assertSame(
            [$existing->id, $extra->id],
            $links->pluck('id')->all(),
            'An existing link should be preserved and the new one appended.'
        );

        $this->assertSame(7, (int) $links->firstWhere('id', $existing->id)->pivot->points);
        $this->assertSame(2, (int) $links->firstWhere('id', $extra->id)->pivot->points);
    }

    public function test_the_edit_form_leaves_already_linked_questions_out_of_the_picker(): void
    {
        $setup = $this->teachingSetup();
        $instructor = $setup['instructor'];
        $course = $setup['course'];
        $class = $setup['class'];

        $reusable = $this->reusableQuestion($instructor);
        $linked = $reusable['question'];

        $free = Question::factory()->create([
            'question_bank_id' => $linked->question_bank_id,
            'created_by' => $instructor->id,
            'question_text' => 'Not yet used anywhere',
        ]);

        $quiz = $this->makeQuiz($course, $class, $instructor);
        $quiz->questions()->attach($linked->id, ['position' => 1, 'points' => 1]);

        $response = $this->actingAs($instructor)
            ->get(route('instructor.courses.quizzes.edit', [$course, $quiz]));

        $response->assertOk()->assertSee('Not yet used anywhere');

        // `test_bank_points[...]` inputs are rendered only by the picker, so
        // their absence is what says "already linked, not offered again".
        $content = $response->getContent();

        $this->assertStringContainsString('name="test_bank_points['.$free->id.']"', $content);
        $this->assertStringNotContainsString('name="test_bank_points['.$linked->id.']"', $content);
    }
}
