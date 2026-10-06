<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use App\Models\User;
use App\Models\Quiz;
use App\Services\QuestionQuotaPicker;
use Illuminate\Support\Collection;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * §9 — configurable random selection with clear "insufficient" validation.
 *
 * The draw must satisfy both the category quotas and the difficulty quotas
 * at the same time. If the two overlap cannot be honoured, the service
 * throws QuestionQuotaException with a message that names the bucket that
 * fell short — never a generic "could not be satisfied".
 */
class TestBankQuotaTest extends TestCase
{
    use CreatesLmsUsers;

    /**
     * Build a pool of questions with known categories and difficulties.
     *
     * Returns [questions => Collection, categories => array<int, QuestionCategory>]
     * where the key is the spec's 'cat' value (0 = uncategorised, 1, 2, etc.)
     */
    private function pool(
        array $specs = [
            ['cat' => 1, 'diff' => 'easy',   'count' => 4],
            ['cat' => 1, 'diff' => 'medium', 'count' => 4],
            ['cat' => 1, 'diff' => 'hard',   'count' => 2],
            ['cat' => 2, 'diff' => 'easy',   'count' => 3],
            ['cat' => 2, 'diff' => 'medium', 'count' => 3],
            ['cat' => 2, 'diff' => 'hard',   'count' => 1],
        ]
    ): array {
        $categories = [];

        foreach ($specs as $spec) {
            $catId = $spec['cat'];
            if (! isset($categories[$catId])) {
                $name = $catId === 0 ? 'Uncategorised' : "Category $catId";
                $categories[$catId] = QuestionCategory::factory()->create([
                    'name' => $name,
                ]);
            }
        }

        $bank = QuestionBank::factory()->create();

        $questions = collect();

        foreach ($specs as $spec) {
            $catId = $spec['cat'];
            $catModel = $catId === 0 ? null : $categories[$catId];

            for ($i = 0; $i < $spec['count']; $i++) {
                $questions->push(Question::factory()->create([
                    'question_bank_id' => $bank->id,
                    'category_id' => $catModel?->id,
                    'difficulty' => $spec['diff'],
                    'question_text' => "Q {$catId}-{$spec['diff']}-{$i}",
                ]));
            }
        }

        return ['questions' => $questions, 'categories' => $categories];
    }

    public function test_simple_total_with_no_quotas_draws_any_questions(): void
    {
        $pool = $this->pool()['questions'];

        $drawn = app(QuestionQuotaPicker::class)->pick($pool, 5);

        $this->assertCount(5, $drawn);
    }

    public function test_category_quota_is_met_when_possible(): void
    {
        $poolData = $this->pool();
        $pool = $poolData['questions'];
        $cats = $poolData['categories'];

        $drawn = app(QuestionQuotaPicker::class)->pick($pool, 7, [
            $cats[1]->id => 4,
            $cats[2]->id => 3,
        ]);

        $cat1 = count(array_filter($drawn, fn (Question $q) => (int) $q->category_id === $cats[1]->id));
        $cat2 = count(array_filter($drawn, fn (Question $q) => (int) $q->category_id === $cats[2]->id));

        $this->assertSame(4, $cat1);
        $this->assertSame(3, $cat2);
    }

    public function test_difficulty_quota_is_met_when_possible(): void
    {
        $pool = $this->pool()['questions'];

        $drawn = app(QuestionQuotaPicker::class)->pick($pool, 7, [], ['easy' => 3, 'medium' => 2, 'hard' => 2]);

        $easy = count(array_filter($drawn, fn (Question $q) => $q->difficulty === 'easy'));
        $medium = count(array_filter($drawn, fn (Question $q) => $q->difficulty === 'medium'));
        $hard = count(array_filter($drawn, fn (Question $q) => $q->difficulty === 'hard'));

        $this->assertSame(3, $easy);
        $this->assertSame(2, $medium);
        $this->assertSame(2, $hard);
    }

    public function test_both_quotas_together_when_the_overlap_exists(): void
    {
        $poolData = $this->pool();
        $pool = $poolData['questions'];
        $cats = $poolData['categories'];

        // 4 from Networking, 3 from Security. Among the 7, 3 easy, 2 medium, 2 hard.
        $drawn = app(QuestionQuotaPicker::class)->pick($pool, 7,
            [$cats[1]->id => 4, $cats[2]->id => 3],
            ['easy' => 3, 'medium' => 2, 'hard' => 2]
        );

        $this->assertCount(7, $drawn);

        $cat1 = count(array_filter($drawn, fn (Question $q) => (int) $q->category_id === $cats[1]->id));
        $cat2 = count(array_filter($drawn, fn (Question $q) => (int) $q->category_id === $cats[2]->id));
        $easy = count(array_filter($drawn, fn (Question $q) => $q->difficulty === 'easy'));
        $medium = count(array_filter($drawn, fn (Question $q) => $q->difficulty === 'medium'));
        $hard = count(array_filter($drawn, fn (Question $q) => $q->difficulty === 'hard'));

        $this->assertSame(4, $cat1);
        $this->assertSame(3, $cat2);
        $this->assertSame(3, $easy);
        $this->assertSame(2, $medium);
        $this->assertSame(2, $hard);
    }

    public function test_refuses_when_category_short(): void
    {
        $pool = $this->pool([['cat' => 1, 'diff' => 'easy', 'count' => 2]])['questions'];

        $this->expectException(\App\Services\QuestionQuotaException::class);
        $this->expectExceptionMessage('Only 2 questions');

        app(QuestionQuotaPicker::class)->pick($pool, 3, [1 => 3]);
    }

    public function test_refuses_when_difficulty_short(): void
    {
        $pool = $this->pool([['cat' => 1, 'diff' => 'easy', 'count' => 3]])['questions'];

        $this->expectException(\App\Services\QuestionQuotaException::class);
        $this->expectExceptionMessage('Difficulty "hard" has only 0 question');

        app(QuestionQuotaPicker::class)->pick($pool, 2, [], ['hard' => 2]);
    }

    public function test_refuses_when_quotas_overlap_cannot_be_honoured_together(): void
    {
        // Cat1 has NO hard questions. Cat2 has only 1 hard.
        // Asking for 4 from Cat1 + 2 from Cat2 = 6 total,
        // and 2 hard overall — but only 1 hard exists in Cat2.
        $poolData = $this->pool([
            ['cat' => 1, 'diff' => 'easy',   'count' => 4],
            ['cat' => 1, 'diff' => 'medium', 'count' => 4],
            // NO hard in cat 1
            ['cat' => 2, 'diff' => 'easy',   'count' => 3],
            ['cat' => 2, 'diff' => 'hard',   'count' => 1],  // only 1 hard
        ]);
        $pool = $poolData['questions'];
        $cats = $poolData['categories'];

        $this->expectException(\App\Services\QuestionQuotaException::class);
        $this->expectExceptionMessage('Difficulty "hard" has only 1 question available, but 2 were requested');

        app(QuestionQuotaPicker::class)->pick($pool, 6,
            [$cats[1]->id => 4, $cats[2]->id => 2],   // 4 from cat1, 2 from cat2
            ['hard' => 2]       // 2 hard overall — but only 1 exists in cat2
        );
    }

    public function test_refuses_when_quota_sum_exceeds_total(): void
    {
        $poolData = $this->pool();
        $pool = $poolData['questions'];
        $cats = $poolData['categories'];

        $this->expectException(\App\Services\QuestionQuotaException::class);
        $this->expectExceptionMessage('add up to');

        app(QuestionQuotaPicker::class)->pick($pool, 5, [$cats[1]->id => 3, $cats[2]->id => 3]);
    }

    public function test_uncategorised_is_not_given_a_quota_unless_requested(): void
    {
        $poolData = $this->pool([
            ['cat' => 0, 'diff' => 'easy', 'count' => 5],  // uncategorised = category_id = null
            ['cat' => 1, 'diff' => 'easy', 'count' => 5],
        ]);
        $pool = $poolData['questions'];
        $cats = $poolData['categories'];

        $drawn = app(QuestionQuotaPicker::class)->pick($pool, 5, [$cats[1]->id => 3]);

        $uncat = count(array_filter($drawn, fn (Question $q) => $q->category_id === null));
        $cat1 = count(array_filter($drawn, fn (Question $q) => (int) $q->category_id === $cats[1]->id));

        $this->assertSame(3, $cat1);
        $this->assertSame(2, $uncat, 'Remainder filled from uncategorised freely');
    }

    public function test_repeated_draws_are_different(): void
    {
        $pool = $this->pool([['cat' => 1, 'diff' => 'easy', 'count' => 10]])['questions'];

        $a = app(QuestionQuotaPicker::class)->pick($pool, 5);
        $b = app(QuestionQuotaPicker::class)->pick($pool, 5);

        $this->assertNotSame($a[0]->id, $b[0]->id, 'Shuffled pool should yield different order');
    }

    public function test_total_larger_than_pool_refused(): void
    {
        $pool = $this->pool([['cat' => 1, 'diff' => 'easy', 'count' => 3]])['questions'];

        $this->expectException(\App\Services\QuestionQuotaException::class);
        $this->expectExceptionMessage('Only 3 question');

        app(QuestionQuotaPicker::class)->pick($pool, 5);
    }

    public function test_controller_integration_random_draw_links_questions(): void
    {
        $user = $this->makeUser('instructor');

        $course = Course::factory()->create();
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $user->id,
        ]);

        $bank = QuestionBank::factory()->create(['created_by' => $user->id]);

        // 4 easy, 4 medium, 2 hard
        foreach (['easy','easy','easy','easy'] as $d) {
            Question::factory()->create(['question_bank_id' => $bank->id, 'difficulty' => $d, 'created_by' => $user->id]);
        }
        foreach (['medium','medium','medium','medium'] as $d) {
            Question::factory()->create(['question_bank_id' => $bank->id, 'difficulty' => $d, 'created_by' => $user->id]);
        }
        foreach (['hard','hard'] as $d) {
            Question::factory()->create(['question_bank_id' => $bank->id, 'difficulty' => $d, 'created_by' => $user->id]);
        }

        $this->actingAs($user)
            ->post(route('instructor.courses.quizzes.store', $course), [
                'class_id' => $class->id,
                'title' => 'Quota Quiz',
                'status' => 'draft',
                'selection_mode' => 'random',
                'draw_total' => 6,
                'draw_difficulty' => ['easy' => 2, 'medium' => 2, 'hard' => 2],
            ])
            ->assertRedirect();

        $quiz = Quiz::where('title', 'Quota Quiz')->firstOrFail();

        $this->assertSame(6, $quiz->questions()->count());

        $diffs = $quiz->questions()->pluck('difficulty')->all();
        $easy = count(array_filter($diffs, fn ($d) => $d === 'easy'));
        $medium = count(array_filter($diffs, fn ($d) => $d === 'medium'));
        $hard = count(array_filter($diffs, fn ($d) => $d === 'hard'));

        $this->assertSame(2, $easy);
        $this->assertSame(2, $medium);
        $this->assertSame(2, $hard);
    }

    public function test_controller_refuses_insufficient_draw_with_clear_message(): void
    {
        $user = $this->makeUser('instructor');

        $course = Course::factory()->create();
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $user->id,
        ]);

        $bank = QuestionBank::factory()->create(['created_by' => $user->id]);

        // Only 2 hard questions exist
        foreach (['hard','hard'] as $d) {
            Question::factory()->create(['question_bank_id' => $bank->id, 'difficulty' => $d, 'created_by' => $user->id]);
        }

        $response = $this->actingAs($user)
            ->post(route('instructor.courses.quizzes.store', $course), [
                'class_id' => $class->id,
                'title' => 'Impossible Quiz',
                'status' => 'draft',
                'selection_mode' => 'random',
                'draw_total' => 5,
                'draw_difficulty' => ['hard' => 4],
            ]);

        $response->assertSessionHasErrors('test_bank_draw');
        $errors = $response->getSession()->get('errors');
        $msg = $errors->first('test_bank_draw');
        $this->assertStringContainsString('Only 2 questions available, but 5 were requested', $msg);
    }
}