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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The exam edit page only ever showed the details form: no way to add a
 * question, no way to import one, and no signpost to the page that could.
 */
class InstructorExamEditPageTest extends TestCase
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

        Storage::fake('public');

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
            'duration_minutes' => 60,
            'created_by' => $this->instructor->id,
            'status' => Exam::STATUS_DRAFT,
        ], $overrides));
    }

    private function csv(array $rows): UploadedFile
    {
        $header = 'question_text,question_type,choice_1,choice_2,choice_3,choice_4,correct_answer';
        $lines = [$header];

        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }

        return UploadedFile::fake()->createWithContent('questions.csv', implode("\n", $lines));
    }

    public function test_the_edit_page_renders(): void
    {
        $exam = $this->exam();

        $this->get(route('instructor.courses.exams.edit', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('Edit Exam', false);
    }

    public function test_the_edit_page_offers_a_way_to_add_a_question_manually(): void
    {
        $exam = $this->exam();

        $this->get(route('instructor.courses.exams.edit', [$this->course, $exam]))
            ->assertOk()
            ->assertSee(route('instructor.courses.exams.questions.store', [$this->course, $exam]), false)
            ->assertSee('name="question_text"', false);
    }

    public function test_the_edit_page_offers_import(): void
    {
        $exam = $this->exam();

        $this->get(route('instructor.courses.exams.edit', [$this->course, $exam]))
            ->assertOk()
            ->assertSee(route('instructor.courses.exams.questions.import', [$this->course, $exam]), false)
            ->assertSee('name="import_file"', false);
    }

    public function test_the_edit_page_lists_the_questions_already_on_the_exam(): void
    {
        $exam = $this->exam();

        $question = Question::create([
            'question_bank_id' => $this->bank->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'An already attached question',
            'default_points' => 5,
            'created_by' => $this->instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order' => 1,
            'points' => 5,
            'is_required' => true,
        ]);

        $this->get(route('instructor.courses.exams.edit', [$this->course, $exam]))
            ->assertOk()
            ->assertSee('An already attached question');
    }

    public function test_a_question_can_be_added_from_the_edit_page(): void
    {
        $exam = $this->exam();

        $this->post(
            route('instructor.courses.exams.questions.store', [$this->course, $exam]),
            [
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'Added from the edit page?',
                'points' => 3,
                'choices' => [
                    ['choice_text' => 'Yes', 'is_correct' => true],
                    ['choice_text' => 'No', 'is_correct' => false],
                ],
            ]
        )->assertRedirect();

        $this->assertSame(1, ExamQuestion::where('exam_id', $exam->id)->count());

        $question = Question::where('question_text', 'Added from the edit page?')->firstOrFail();

        $this->assertSame(1, $question->choices()->where('is_correct', true)->count());
    }

    public function test_questions_can_be_imported_from_csv(): void
    {
        $exam = $this->exam();

        $response = $this->post(
            route('instructor.courses.exams.questions.import', [$this->course, $exam]),
            ['import_file' => $this->csv([
                ['What is 2+2?', 'multiple_choice', '3', '4', '5', '6', 'b'],
                ['Capital of France?', 'multiple_choice', 'Paris', 'Rome', 'Madrid', 'Bonn', 'a'],
            ])]
        );

        $response->assertRedirect();

        $this->assertSame(2, ExamQuestion::where('exam_id', $exam->id)->count());

        $imported = Question::whereIn('question_text', ['What is 2+2?', 'Capital of France?'])->get();

        $this->assertCount(2, $imported);

        // Every imported multiple-choice question must have choices and one answer.
        foreach ($imported as $question) {
            $this->assertSame(4, $question->choices()->count());
            $this->assertSame(1, $question->choices()->where('is_correct', true)->count());
        }
    }

    public function test_imported_questions_are_numbered_and_keep_their_points(): void
    {
        $exam = $this->exam();

        // A points column so the values carried through can be checked.
        $header = 'question_text,question_type,choice_1,choice_2,choice_3,choice_4,correct_answer,points';
        $csv = UploadedFile::fake()->createWithContent('questions.csv', implode("\n", [
            $header,
            'Q one,multiple_choice,a,b,c,d,a,5',
            'Q two,multiple_choice,a,b,c,d,b,7',
        ]));

        $this->post(
            route('instructor.courses.exams.questions.import', [$this->course, $exam]),
            ['import_file' => $csv]
        )->assertRedirect();

        $this->assertSame(
            [1, 2],
            ExamQuestion::where('exam_id', $exam->id)->orderBy('order')->pluck('order')->all()
        );

        // 5 + 7, taken from the pivot rather than each question's default.
        $this->assertSame(12.0, $exam->fresh()->getTotalPoints());
    }

    public function test_import_defaults_points_to_one_when_the_file_omits_them(): void
    {
        $exam = $this->exam();

        $this->post(
            route('instructor.courses.exams.questions.import', [$this->course, $exam]),
            ['import_file' => $this->csv([['Q', 'multiple_choice', 'a', 'b', 'c', 'd', 'a']])]
        )->assertRedirect();

        $this->assertSame(1.0, $exam->fresh()->getTotalPoints());
    }

    public function test_import_rejects_an_unsupported_file_type(): void
    {
        $exam = $this->exam();

        $this->post(
            route('instructor.courses.exams.questions.import', [$this->course, $exam]),
            ['import_file' => UploadedFile::fake()->create('questions.pdf', 5, 'application/pdf')]
        )->assertSessionHasErrors('import_file');

        $this->assertSame(0, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    public function test_import_cannot_run_on_a_published_exam(): void
    {
        $exam = $this->exam(['status' => Exam::STATUS_PUBLISHED]);

        $this->post(
            route('instructor.courses.exams.questions.import', [$this->course, $exam]),
            ['import_file' => $this->csv([['Q', 'multiple_choice', 'a', 'b', 'c', 'd', 'a']])]
        )->assertStatus(422);

        $this->assertSame(0, ExamQuestion::where('exam_id', $exam->id)->count());
    }

    public function test_import_of_another_instructors_exam_is_refused(): void
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

        $this->post(
            route('instructor.courses.exams.questions.import', [$this->course, $theirExam]),
            ['import_file' => $this->csv([['Q', 'multiple_choice', 'a', 'b', 'c', 'd', 'a']])]
        )->assertForbidden();
    }
}