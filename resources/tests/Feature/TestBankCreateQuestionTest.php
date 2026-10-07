<?php

namespace Tests\Feature;

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
 * Creating a question (and its answer key) straight from the Test Bank list.
 *
 * Previously the only way in was to open a bank's long edit page, which is why
 * manual creation looked impossible from the library.
 */
class TestBankCreateQuestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected QuestionBank $bank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);

        $this->bank = QuestionBank::create([
            'title' => 'Networking Essentials',
            'course_id' => null,
            'class_id' => null,
            'created_by' => $this->instructor->id,
            'is_shared' => false,
            'status' => 'active',
        ]);
    }

    public function test_the_test_bank_page_offers_an_add_question_action(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.test_bank.index'))
            ->assertOk()
            ->assertSee('Add Question', false);
    }

    public function test_instructor_can_create_a_multiple_choice_question_with_an_answer_key(): void
    {
        $response = $this->actingAs($this->instructor)->post(
            route('instructor.test_bank.questions.store'),
            [
                'question_bank_id' => $this->bank->id,
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'Which layer of the OSI model does a router operate on?',
                'difficulty' => 'medium',
                'default_points' => 2,
                'status' => 'active',
                'new' => [
                    ['text' => 'Data link', 'correct' => null],
                    ['text' => 'Network', 'correct' => '1'],
                    ['text' => 'Transport', 'correct' => null],
                    ['text' => 'Session', 'correct' => null],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('questions', [
            'question_bank_id' => $this->bank->id,
            'question_text' => 'Which layer of the OSI model does a router operate on?',
            'created_by' => $this->instructor->id,
        ]);

        $question = Question::where('question_text', 'Which layer of the OSI model does a router operate on?')->firstOrFail();

        // The answer key is the whole point, so assert it actually persisted.
        $this->assertSame(4, $question->choices()->count());
        $this->assertSame(1, $question->choices()->where('is_correct', true)->count());
        $this->assertSame('Network', $question->choices()->where('is_correct', true)->first()->choice_text);
    }

    public function test_instructor_can_create_an_identification_question_with_an_accepted_answer(): void
    {
        $response = $this->actingAs($this->instructor)->post(
            route('instructor.test_bank.questions.store'),
            [
                'question_bank_id' => $this->bank->id,
                'question_type' => Question::TYPE_IDENTIFICATION,
                'question_text' => 'What does TCP stand for?',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
                'is_case_sensitive' => '1',
                'new' => [
                    ['text' => 'Transmission Control Protocol', 'correct' => '1'],
                    ['text' => '', 'correct' => null],
                    ['text' => '', 'correct' => null],
                    ['text' => '', 'correct' => null],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $question = Question::where('question_text', 'What does TCP stand for?')->firstOrFail();

        $this->assertTrue((bool) $question->is_case_sensitive);
        $this->assertSame(1, $question->choices()->count(), 'Blank rows must not be stored.');
        $this->assertTrue((bool) $question->choices()->first()->is_correct);
    }

    public function test_a_choice_question_without_a_marked_answer_is_refused(): void
    {
        $response = $this->actingAs($this->instructor)->post(
            route('instructor.test_bank.questions.store'),
            [
                'question_bank_id' => $this->bank->id,
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'Pick one, but nothing is ticked.',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
                'new' => [
                    ['text' => 'A', 'correct' => null],
                    ['text' => 'B', 'correct' => null],
                ],
            ]
        );

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('questions', ['question_text' => 'Pick one, but nothing is ticked.']);
    }

    public function test_a_question_cannot_be_parked_in_someone_elses_private_bank(): void
    {
        $other = User::factory()->instructor()->create(['status' => 'active']);

        $foreignBank = QuestionBank::create([
            'title' => 'Somebody Elses Bank',
            'course_id' => null,
            'class_id' => null,
            'created_by' => $other->id,
            'is_shared' => false,
            'status' => 'active',
        ]);

        $this->actingAs($this->instructor)
            ->post(route('instructor.test_bank.questions.store'), [
                'question_bank_id' => $foreignBank->id,
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'Should never be created.',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
                'new' => [
                    ['text' => 'A', 'correct' => '1'],
                    ['text' => 'B', 'correct' => null],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('questions', ['question_text' => 'Should never be created.']);
    }

    public function test_an_essay_needs_no_answer_key(): void
    {
        $this->actingAs($this->instructor)
            ->post(route('instructor.test_bank.questions.store'), [
                'question_bank_id' => $this->bank->id,
                'question_type' => Question::TYPE_ESSAY,
                'question_text' => 'Explain how a switch learns MAC addresses.',
                'difficulty' => 'hard',
                'default_points' => 5,
                'status' => 'active',
            ])
            ->assertSessionHasNoErrors();

        $question = Question::where('question_text', 'Explain how a switch learns MAC addresses.')->firstOrFail();

        $this->assertSame(0, $question->choices()->count());
    }

    public function test_a_student_cannot_reach_the_create_endpoint(): void
    {
        $student = User::factory()->student()->create(['status' => 'active']);

        $this->actingAs($student)
            ->post(route('instructor.test_bank.questions.store'), [
                'question_bank_id' => $this->bank->id,
                'question_type' => Question::TYPE_ESSAY,
                'question_text' => 'Nope.',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
            ])
            ->assertForbidden();
    }

    public function test_the_new_question_is_listed_in_the_library_after_creating_it(): void
    {
        $this->actingAs($this->instructor)->post(
            route('instructor.test_bank.questions.store'),
            [
                'question_bank_id' => $this->bank->id,
                'question_type' => Question::TYPE_TRUE_FALSE,
                'question_text' => 'TCP guarantees delivery in order.',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
                'new' => [
                    ['text' => 'True', 'correct' => '1'],
                    ['text' => 'False', 'correct' => null],
                ],
            ]
        )->assertRedirect(route('instructor.test_bank.index', ['bank_id' => $this->bank->id]));

        $this->actingAs($this->instructor)
            ->get(route('instructor.test_bank.index'))
            ->assertOk()
            ->assertSee('TCP guarantees delivery in order.', false);
    }
}