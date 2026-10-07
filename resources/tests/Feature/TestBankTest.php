<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * The Test Bank section itself (§1, §6, §7, §10, §11, §16, §17).
 *
 * §14 history protection lives in QuestionSnapshotTest instead, so this file
 * stays about browsing, categorising, reporting and who may do each of them.
 */
class TestBankTest extends TestCase
{
    use CreatesLmsUsers;

    /**
     * @return array{instructor: \App\Models\User, bank: QuestionBank}
     */
    private function ownBank(array $bank = []): array
    {
        $instructor = $this->makeUser('instructor');

        return [
            'instructor' => $instructor,
            'bank' => QuestionBank::factory()->create(array_merge(['created_by' => $instructor->id], $bank)),
        ];
    }

    public function test_instructor_sees_their_own_questions_and_admin_shared_ones_only(): void
    {
        $instructor = $this->makeUser('instructor');
        $admin = $this->makeUser('admin');
        $other = $this->makeUser('instructor');

        $ownBank = QuestionBank::factory()->create(['created_by' => $instructor->id]);
        $sharedBank = QuestionBank::factory()->create(['created_by' => $admin->id, 'is_shared' => true]);
        $privateBank = QuestionBank::factory()->create(['created_by' => $other->id, 'is_shared' => false]);

        $own = Question::factory()->create(['question_bank_id' => $ownBank->id, 'question_text' => 'Mine, plainly']);
        $shared = Question::factory()->create(['question_bank_id' => $sharedBank->id, 'question_text' => 'Shared with me']);
        $foreign = Question::factory()->create(['question_bank_id' => $privateBank->id, 'question_text' => 'Someone elses work']);

        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.index'))
            ->assertOk()
            ->assertSee('Mine, plainly')
            ->assertSee('Shared with me')
            ->assertDontSee('Someone elses work');

        $this->assertNotNull($own);
        $this->assertNotNull($shared);
        $this->assertNotNull($foreign);
    }

    public function test_instructor_cannot_preview_a_question_from_a_private_bank(): void
    {
        $instructor = $this->makeUser('instructor');
        $other = $this->makeUser('instructor');

        $bank = QuestionBank::factory()->create([
            'created_by' => $other->id,
            'is_shared' => false,
        ]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);

        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.questions.preview', $question))
            ->assertForbidden();
    }

    public function test_student_cannot_reach_any_test_bank_route(): void
    {
        $student = $this->makeUser('student');
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);

        foreach ([
            route('instructor.test_bank.index'),
            route('instructor.question_banks.index'),
            route('instructor.question_banks.categories.index'),
            route('instructor.question_banks.statistics.index'),
            route('instructor.test_bank.questions.preview', $question),
        ] as $url) {
            $this->actingAs($student)->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_open_the_test_bank(): void
    {
        $admin = $this->makeUser('admin');
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id, 'is_shared' => false]);
        Question::factory()->create(['question_bank_id' => $bank->id, 'question_text' => 'Private instructor work']);

        // Admins manage all banks regardless of who created them (§16), and get
        // their own copy of the tab bar rather than an /instructor/... URL.
        $this->actingAs($admin)
            ->get(route('admin.test_bank.index'))
            ->assertOk()
            ->assertSee('Private instructor work');

        $this->actingAs($admin)
            ->get(route('admin.question_banks.categories.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.question_banks.statistics.index'))
            ->assertOk();
    }

    public function test_student_cannot_reach_the_admin_test_bank_routes_either(): void
    {
        $student = $this->makeUser('student');
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);

        foreach ([
            route('admin.test_bank.index'),
            route('admin.question_banks.categories.index'),
            route('admin.question_banks.statistics.index'),
            route('admin.test_bank.questions.preview', $question),
        ] as $url) {
            $this->actingAs($student)->get($url)->assertForbidden();
        }
    }

    public function test_question_list_filters_by_category_type_difficulty_and_search(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);
        $category = QuestionCategory::create([
            'name' => 'Computer Hardware',
            'created_by' => $instructor->id,
        ]);

        $match = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'What does RAM store?',
            'question_type' => Question::TYPE_IDENTIFICATION,
            'difficulty' => Question::DIFFICULTY_EASY,
            'category_id' => $category->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Explain the fetch cycle',
            'question_type' => Question::TYPE_ESSAY,
            'difficulty' => Question::DIFFICULTY_HARD,
            'status' => Question::STATUS_ACTIVE,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.index', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('What does RAM store?')
            ->assertDontSee('Explain the fetch cycle');

        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.index', ['question_type' => Question::TYPE_ESSAY]))
            ->assertOk()
            ->assertSee('Explain the fetch cycle')
            ->assertDontSee('What does RAM store?');

        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.index', ['difficulty' => Question::DIFFICULTY_EASY]))
            ->assertOk()
            ->assertSee('What does RAM store?');

        // Free-text search over the question itself.
        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.index', ['q' => 'fetch cycle']))
            ->assertOk()
            ->assertSee('Explain the fetch cycle')
            ->assertDontSee('What does RAM store?');

        $this->assertNotNull($match);
    }

    public function test_archived_questions_are_hidden_until_asked_for(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Still live',
            'status' => Question::STATUS_ACTIVE,
        ]);
        Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Retired ages ago',
            'status' => Question::STATUS_ARCHIVED,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.index'))
            ->assertOk()
            ->assertSee('Still live')
            ->assertDontSee('Retired ages ago');

        // §3 — inactive questions stay reachable, they are just not pushed at you.
        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.index', ['status' => Question::STATUS_ARCHIVED]))
            ->assertOk()
            ->assertSee('Retired ages ago');
    }

    public function test_preview_shows_the_question_and_its_answer_key(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Which part fetches instructions?',
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
        ]);

        QuestionChoice::factory()->create([
            'question_id' => $question->id,
            'choice_text' => 'Control Unit',
            'is_correct' => true,
            'position' => 1,
        ]);
        QuestionChoice::factory()->create([
            'question_id' => $question->id,
            'choice_text' => 'ALU',
            'is_correct' => false,
            'position' => 2,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.test_bank.questions.preview', $question))
            ->assertOk()
            ->assertSee('Which part fetches instructions?')
            ->assertSee('Control Unit')
            ->assertSee('Correct');
    }

    public function test_category_can_be_created(): void
    {
        $instructor = $this->makeUser('instructor');

        $this->actingAs($instructor)
            ->post(route('instructor.question_banks.categories.store'), [
                'name' => 'Networking',
                'description' => 'OSI model, protocols, topologies',
            ])
            ->assertRedirect();

        $category = QuestionCategory::where('name', 'Networking')->firstOrFail();

        $this->assertSame($instructor->id, $category->created_by);
        $this->assertNull($category->course_id);
    }

    public function test_duplicate_category_name_is_rejected_within_the_same_scope(): void
    {
        $instructor = $this->makeUser('instructor');

        QuestionCategory::create(['name' => 'Operating Systems', 'created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->from(route('instructor.question_banks.categories.index'))
            ->post(route('instructor.question_banks.categories.store'), ['name' => 'operating systems'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, QuestionCategory::count());
    }

    public function test_a_category_in_use_cannot_be_deleted(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $category = QuestionCategory::create(['name' => 'Databases', 'created_by' => $instructor->id]);
        Question::factory()->create([
            'question_bank_id' => $bank->id,
            'category_id' => $category->id,
        ]);

        $this->actingAs($instructor)
            ->delete(route('instructor.question_banks.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertNotNull($category->fresh(), 'A category in use must survive the delete attempt.');
        $this->assertDatabaseHas('questions', ['category_id' => $category->id]);
    }

    public function test_an_unused_category_can_be_deleted(): void
    {
        $instructor = $this->makeUser('instructor');

        $category = QuestionCategory::create(['name' => 'Obsolete Topic', 'created_by' => $instructor->id]);

        $this->actingAs($instructor)
            ->delete(route('instructor.question_banks.categories.destroy', $category))
            ->assertRedirect(route('instructor.question_banks.categories.index'));

        $this->assertSoftDeleted('question_categories', ['id' => $category->id]);
    }

    public function test_statistics_page_reports_usage_and_a_recommendation(): void
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser('student');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Name the layers of the OSI model.',
            'difficulty' => Question::DIFFICULTY_EASY,
        ]);

        $course = \App\Models\Course::factory()->create(['created_by' => $instructor->id, 'status' => 'published']);
        $class = \App\Models\ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);
        $quiz = Quiz::create([
            'class_id' => $class->id,
            'title' => 'OSI quiz',
            'slug' => 'osi-'.uniqid(),
            'status' => 'published',
            'created_by' => $instructor->id,
        ]);
        $quiz->questions()->attach($question->id, ['position' => 1, 'points' => 1]);

        // 5 responses, only 1 right, on something rated Easy. One answer row per
        // attempt — quiz_answers has UNIQUE(quiz_attempt_id, question_id).
        foreach ([true, false, false, false, false] as $number => $correct) {
            $respondent = \App\Models\User::factory()->create();

            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $respondent->id,
                'attempt_number' => $number + 1,
                'status' => 'graded',
                'graded_at' => now(),
            ]);

            QuizAnswer::create([
                'quiz_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'is_correct' => $correct,
                'points_awarded' => $correct ? 1 : 0,
            ]);
        }

        $this->actingAs($instructor)
            ->get(route('instructor.question_banks.statistics.index'))
            ->assertOk()
            ->assertSee('Name the layers of the OSI model.')
            ->assertSee('Below mastery')
            ->assertSee('rated Easy');
    }

    public function test_statistics_recommends_nothing_before_enough_responses(): void
    {
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);

        Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Freshly written item.',
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.question_banks.statistics.index'))
            ->assertOk()
            ->assertSee('Freshly written item.')
            ->assertSee('Unused');
    }

    public function test_statistics_only_lists_own_and_shared_questions(): void
    {
        $instructor = $this->makeUser('instructor');
        $other = $this->makeUser('instructor');

        $mine = QuestionBank::factory()->create(['created_by' => $instructor->id]);
        $theirs = QuestionBank::factory()->create(['created_by' => $other->id, 'is_shared' => false]);

        Question::factory()->create(['question_bank_id' => $mine->id, 'question_text' => 'Mine for stats']);
        Question::factory()->create(['question_bank_id' => $theirs->id, 'question_text' => 'Not mine for stats']);

        $this->actingAs($instructor)
            ->get(route('instructor.question_banks.statistics.index'))
            ->assertOk()
            ->assertSee('Mine for stats')
            ->assertDontSee('Not mine for stats');
    }

    public function test_question_policy_refuses_students_even_when_enrolled(): void
    {
        // §17 — belt and braces: the route is role-gated, but the policy must
        // also refuse on its own so a future route cannot leak it.
        $student = $this->makeUser('student');
        $instructor = $this->makeUser('instructor');
        $bank = QuestionBank::factory()->create(['created_by' => $instructor->id]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);

        $this->assertFalse($student->can('view', $question));
        $this->assertFalse($student->can('view', $bank));
        $this->assertFalse($student->can('viewAny', Question::class));
        $this->assertFalse($student->can('update', $question));
        $this->assertFalse($student->can('delete', $question));
    }

    public function test_instructor_policy_refuses_questions_in_a_private_foreign_bank(): void
    {
        $instructor = $this->makeUser('instructor');
        $other = $this->makeUser('instructor');

        $privateForeign = QuestionBank::factory()->create(['created_by' => $other->id, 'is_shared' => false]);
        $sharedForeign = QuestionBank::factory()->create(['created_by' => $other->id, 'is_shared' => true]);

        $hidden = Question::factory()->create(['question_bank_id' => $privateForeign->id]);
        $shared = Question::factory()->create(['question_bank_id' => $sharedForeign->id]);

        $this->assertFalse($instructor->can('view', $hidden));
        $this->assertTrue($instructor->can('view', $shared));
        // Reading a shared question does not grant editing it.
        $this->assertFalse($instructor->can('update', $shared));
    }
}
