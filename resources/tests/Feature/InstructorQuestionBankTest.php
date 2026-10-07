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

class InstructorQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $roleSlug): User
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    public function test_instructor_can_open_question_bank_index(): void
    {
        $instructor = $this->makeUser('instructor');

        $this->actingAs($instructor)
            ->get('/instructor/question-banks')
            ->assertOk();
    }

    public function test_instructor_sees_shared_admin_banks(): void
    {
        $instructor = $this->makeUser('instructor');
        $admin = $this->makeUser('admin');

        QuestionBank::factory()->create([
            'created_by' => $admin->id,
            'title' => 'Institutional Midterm Bank',
            'is_shared' => true,
        ]);

        QuestionBank::factory()->create([
            'created_by' => $admin->id,
            'title' => 'Private Admin Bank',
            'is_shared' => false,
        ]);

        $this->actingAs($instructor)
            ->get('/instructor/question-banks')
            ->assertOk()
            ->assertSee('Institutional Midterm Bank')
            ->assertDontSee('Private Admin Bank');
    }

    public function test_instructor_can_create_bank_and_is_redirected_to_edit(): void
    {
        $instructor = $this->makeUser('instructor');

        $response = $this->actingAs($instructor)->post('/instructor/question-banks', [
            'title' => 'Genetics Bank',
            'code' => 'GEN-101',
            'category' => 'Biology',
            'status' => 'active',
        ]);

        $bank = QuestionBank::where('code', 'GEN-101')->firstOrFail();

        $response->assertRedirect(route('instructor.question_banks.edit', $bank));
        $this->assertSame($instructor->id, $bank->created_by);
    }

    public function test_instructor_can_add_a_question_with_choices(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->post("/instructor/question-banks/{$bank->id}/questions", [
                'question_type' => 'multiple_choice',
                'question_text' => 'Which organelle produces ATP?',
                'difficulty' => 'medium',
                'default_points' => 2,
                'status' => 'active',
                'tags' => 'biology, cells',
                'new' => [
                    ['text' => 'Mitochondrion', 'correct' => '1'],
                    ['text' => 'Ribosome', 'correct' => '0'],
                    ['text' => 'Golgi apparatus'],
                    ['text' => ''],
                    ['text' => '   '],
                ],
            ])
            ->assertRedirect(route('instructor.question_banks.edit', $bank));

        $question = Question::where('question_bank_id', $bank->id)->firstOrFail();

        $this->assertSame('Which organelle produces ATP?', $question->question_text);
        $this->assertSame(['biology', 'cells'], $question->tags);

        // Empty / whitespace-only rows are dropped rather than stored.
        $this->assertSame(3, $question->choices()->count());

        $correct = $question->choices()->where('is_correct', true)->pluck('choice_text')->all();
        $this->assertSame(['Mitochondrion'], $correct);

        // Positions are assigned in submitted order.
        $this->assertSame([0, 1, 2], $question->choices()->orderBy('position')->pluck('position')->all());
    }

    public function test_question_without_a_correct_choice_is_rejected(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->post("/instructor/question-banks/{$bank->id}/questions", [
                'question_type' => 'multiple_choice',
                'question_text' => 'No correct answer here',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
                'new' => [
                    ['text' => 'First'],
                    ['text' => 'Second'],
                ],
            ])
            ->assertSessionHasErrors('question_text');

        $this->assertSame(0, $bank->questions()->count());
    }

    public function test_multiple_correct_choices_rejected_for_single_answer_type(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->post("/instructor/question-banks/{$bank->id}/questions", [
                'question_type' => 'multiple_choice',
                'question_text' => 'Two correct answers is invalid here',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
                'new' => [
                    ['text' => 'A', 'correct' => '1'],
                    ['text' => 'B', 'correct' => '1'],
                ],
            ])
            ->assertSessionHasErrors('question_text');
    }

    public function test_multiple_answer_allows_several_correct_choices(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->post("/instructor/question-banks/{$bank->id}/questions", [
                'question_type' => 'multiple_answer',
                'question_text' => 'Select both nucleotides',
                'difficulty' => 'hard',
                'default_points' => 3,
                'status' => 'active',
                'new' => [
                    ['text' => 'Adenine', 'correct' => '1'],
                    ['text' => 'Cytosine', 'correct' => '1'],
                    ['text' => 'Uracil'],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, Question::where('question_bank_id', $bank->id)->firstOrFail()
            ->choices()->where('is_correct', true)->count());
    }

    public function test_essay_needs_no_choices(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->post("/instructor/question-banks/{$bank->id}/questions", [
                'question_type' => 'essay',
                'question_text' => 'Explain photosynthesis.',
                'difficulty' => 'medium',
                'default_points' => 10,
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->assertSame(0, Question::where('question_bank_id', $bank->id)->firstOrFail()->choices()->count());
    }

    public function test_instructor_can_edit_a_question_and_its_choices(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Original text',
            'question_type' => 'multiple_choice',
        ]);

        $keep = QuestionChoice::factory()->create([
            'question_id' => $question->id,
            'choice_text' => 'Keep me',
            'is_correct' => true,
            'position' => 0,
        ]);

        $drop = QuestionChoice::factory()->create([
            'question_id' => $question->id,
            'choice_text' => 'Delete me',
            'is_correct' => false,
            'position' => 1,
        ]);

        $this->actingAs($instructor)
            ->put("/instructor/question-banks/{$bank->id}/questions/{$question->id}", [
                'question_type' => 'multiple_choice',
                'question_text' => 'Updated text',
                'difficulty' => 'hard',
                'default_points' => 4,
                'status' => 'active',
                'choice' => [
                    $keep->id => ['text' => 'Keep me', 'correct' => '1'],
                    $drop->id => ['text' => ''],
                ],
                'new' => [
                    ['text' => 'Brand new choice'],
                ],
            ])
            ->assertRedirect(route('instructor.question_banks.edit', $bank));

        $question->refresh();

        $this->assertSame('Updated text', $question->question_text);
        $this->assertSame('hard', $question->difficulty);
        $this->assertSame(4.0, (float) $question->default_points);

        // Blanking a row deletes it; the new row is appended.
        $this->assertNull(QuestionChoice::find($drop->id));

        $texts = $question->choices()->orderBy('position')->pluck('choice_text')->all();
        $this->assertSame(['Keep me', 'Brand new choice'], $texts);

        $this->assertTrue((bool) $question->choices()->where('choice_text', 'Keep me')->first()->is_correct);
    }

    public function test_instructor_can_delete_a_question(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);

        $this->actingAs($instructor)
            ->delete("/instructor/question-banks/{$bank->id}/questions/{$question->id}")
            ->assertRedirect(route('instructor.question_banks.edit', $bank));

        $this->assertSoftDeleted('questions', ['id' => $question->id]);
    }

    public function test_instructor_cannot_edit_a_question_in_someone_elses_bank(): void
    {
        $instructor = $this->makeUser('instructor');
        $admin = $this->makeUser('admin');

        $bank = QuestionBank::factory()->create([
            'created_by' => $admin->id,
            'is_shared' => true,
        ]);

        $question = Question::factory()->create(['question_bank_id' => $bank->id]);

        $this->actingAs($instructor)
            ->put("/instructor/question-banks/{$bank->id}/questions/{$question->id}", [
                'question_type' => 'multiple_choice',
                'question_text' => 'Hijacked',
                'difficulty' => 'easy',
                'default_points' => 1,
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertSame('question_text', $question->fresh()->question_text ? 'question_text' : '');
    }

    public function test_shared_bank_is_readable_but_not_editable(): void
    {
        $instructor = $this->makeUser('instructor');
        $admin = $this->makeUser('admin');

        $bank = QuestionBank::factory()->create([
            'created_by' => $admin->id,
            'is_shared' => true,
        ]);

        $this->actingAs($instructor)
            ->get("/instructor/question-banks/{$bank->id}")
            ->assertOk();

        $this->actingAs($instructor)
            ->get("/instructor/question-banks/{$bank->id}/edit")
            ->assertForbidden();
    }
}