<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Module;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use App\Services\QuizImportService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Importing a question file crawled, because the work was done one question at
 * a time inside its own transaction, with the question bank looked up again for
 * every row and a COUNT(*) to derive each question's position.
 *
 * The import must scale with the rows written, not with the rows read, and must
 * not issue a query per row just to work out things that cannot change.
 */
class QuizImportPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['created_by' => $instructor->id]);

        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
        ]);

        $module = Module::factory()->create(['course_id' => $course->id]);

        $this->quiz = Quiz::create([
            'class_id' => $class->id,
            'module_id' => $module->id,
            'title' => 'Imported Quiz',
            'slug' => 'imported-quiz-'.$module->id,
            'attempt_limit' => 1,
            'passing_score' => 70,
            'total_points' => 100,
            'status' => 'draft',
            'created_by' => $instructor->id,
        ]);
    }

    /** Build a CSV with $count four-option multiple-choice questions. */
    private function csv(int $count): string
    {
        $rows = ['question_text,question_type,choice_1,choice_2,choice_3,choice_4,correct_answer'];

        for ($i = 1; $i <= $count; $i++) {
            $rows[] = "Question {$i},multiple_choice,Option A,Option B,Option C,Option D,a";
        }

        $path = tempnam(sys_get_temp_dir(), 'imp').'.csv';
        file_put_contents($path, implode("\n", $rows));

        return $path;
    }

    private function import(int $count): array
    {
        return app(QuizImportService::class)->importQuestionsFromFile(
            $this->csv($count),
            $this->quiz,
            $this->quiz->created_by,
            null,
            'csv'
        );
    }

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();
        } finally {
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();
        }

        return $queries;
    }

    public function test_import_creates_every_question_and_its_choices(): void
    {
        $result = $this->import(12);

        $this->assertSame(12, $result['created'], 'Every row must still be imported.');
        $this->assertSame([], $result['errors']);
        $this->assertSame(12, Question::count());
        $this->assertSame(48, QuestionChoice::count());
    }

    public function test_import_cost_grows_with_rows_written_not_rows_read(): void
    {
        // Two quizzes in one database: each starts empty, so the marginal cost
        // of the extra rows is measured without resetting the schema.
        $smallQuiz = $this->secondQuiz('Small Import');
        $largeQuiz = $this->secondQuiz('Large Import');

        $small = $this->countQueries(fn () => $this->importInto($smallQuiz, 10));
        $large = $this->countQueries(fn () => $this->importInto($largeQuiz, 40));

        $perExtraQuestion = ($large - $small) / 30;

        // A well-batched import writes a question, its pivot row and its
        // choices: roughly 3 queries per question. The previous implementation
        // cost closer to 10 per question (transaction + bank lookup + COUNT +
        // one insert per choice), which is what made large files crawl.
        $this->assertLessThan(
            5.0,
            $perExtraQuestion,
            sprintf(
                'Import cost per additional question was %.1f queries (10 rows = %d, 40 rows = %d).',
                $perExtraQuestion,
                $small,
                $large
            )
        );
    }

    private function secondQuiz(string $title): Quiz
    {
        return Quiz::create([
            'class_id' => $this->quiz->class_id,
            'module_id' => $this->quiz->module_id,
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'attempt_limit' => 1,
            'passing_score' => 70,
            'total_points' => 100,
            'status' => 'draft',
            'created_by' => $this->quiz->created_by,
        ]);
    }

    private function importInto(Quiz $quiz, int $count): array
    {
        return app(QuizImportService::class)->importQuestionsFromFile(
            $this->csv($count),
            $quiz,
            $quiz->created_by,
            null,
            'csv'
        );
    }

    public function test_question_bank_is_resolved_once_not_once_per_row(): void
    {
        // One bank per quiz, however many questions are imported.
        $this->import(15);

        $this->assertSame(1, QuestionBank::count());
        $this->assertSame(15, Question::where('question_bank_id', QuestionBank::first()->id)->count());
    }

    public function test_positions_are_sequential_without_a_count_query_each_time(): void
    {
        $this->import(10);

        $positions = \App\Models\QuizQuestion::where('quiz_id', $this->quiz->id)
            ->orderBy('id')
            ->pluck('position')
            ->all();

        $this->assertSame(range(1, 10), $positions);
    }

}