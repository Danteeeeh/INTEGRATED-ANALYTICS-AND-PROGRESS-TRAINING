<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\MediaFile;
use App\Models\Module;
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
 * Two problems on the student side:
 *
 *  1. An exam with no questions could still be started, so a student sat an
 *     empty paper and "completed" it.
 *  2. Attached lesson materials were neither listed readably nor openable.
 */
class StudentExamAndMaterialUxTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    private ClassModel $class;

    private Module $module;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        Storage::fake('public');
        Storage::fake('local');

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create(['title' => 'Computer Science', 'status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $this->module = Module::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'published',
        ]);

        $this->lesson = Lesson::factory()->create([
            'module_id' => $this->module->id,
            'title' => 'Introduction',
            'status' => 'published',
        ]);

        Enrollment::create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);

        $this->actingAs($this->student);
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
            'passing_score_percent' => 60,
            'status' => Exam::STATUS_PUBLISHED,
            'created_by' => $this->student->id,
        ], $overrides));
    }

    private function attachQuestion(Exam $exam, float $points = 10): void
    {
        $bank = QuestionBank::create([
            'course_id' => $this->course->id,
            'title' => 'Bank '.uniqid(),
            'created_by' => $this->student->id,
            'status' => 'active',
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'question_type' => Question::TYPE_MULTIPLE_CHOICE,
            'question_text' => 'A bank question',
            'default_points' => $points,
            'created_by' => $this->student->id,
            'status' => Question::STATUS_ACTIVE,
        ]);

        QuestionChoice::create([
            'question_id' => $question->id,
            'choice_text' => 'Right',
            'is_correct' => true,
            'position' => 1,
            'points' => 0,
        ]);

        ExamQuestion::create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order' => 1,
            'points' => $points,
            'is_required' => true,
        ]);
    }

    // ── An empty exam must not be sittable ────────────────────────

    public function test_an_exam_with_no_questions_cannot_be_started(): void
    {
        $empty = $this->exam();

        $response = $this->get(
            route('student.courses.exams.attempt.start', [$this->course, $empty])
        );

        $this->assertSame(
            0,
            ExamAttemptCount($empty->id),
            'An exam with no questions must not record an attempt.'
        );

        $response->assertRedirect(route('student.courses.exams.show', [$this->course, $empty]));
    }

    public function test_an_exam_with_questions_still_starts_normally(): void
    {
        $exam = $this->exam();
        $this->attachQuestion($exam);

        $this->get(route('student.courses.exams.attempt.start', [$this->course, $exam]))->assertOk();

        $this->assertSame(1, ExamAttemptCount($exam->id));
    }

    public function test_the_exam_list_shows_how_many_questions_each_one_has(): void
    {
        $withQuestions = $this->exam(['title' => 'Has Questions']);
        $this->attachQuestion($withQuestions, 10);

        $this->exam(['title' => 'Still Being Built']);

        $this->get(route('student.courses.exams.index', $this->course))
            ->assertOk()
            ->assertSee('Has Questions')
            ->assertSee('Still Being Built')
            // A student must be able to tell an empty exam from a real one.
            ->assertSee('0 questions', false);
    }

    // ── Attached lesson materials ──────────────────────────────────

    public function test_a_lesson_material_is_listed_and_can_be_opened(): void
    {
        $media = app(\App\Services\FileUploadService::class)->uploadFile(
            UploadedFile::fake()->create('Handout.pdf', 20, 'application/pdf'),
            'lesson_materials',
            ['disk' => 'public']
        );

        LessonMaterial::create([
            'lesson_id' => $this->lesson->id,
            'media_file_id' => $media->id,
            'position' => 1,
        ]);

        $response = $this->get(
            route('student.courses.modules.lessons.show', [$this->course, $this->module, $this->lesson])
        );

        $response->assertOk();

        // Readable name, and a way to preview it in place.
        $response->assertSee('Handout.pdf');
        $response->assertSee('data-file-viewer-open', false);

        // And a way to download it.
        $response->assertSee(route('files.download', ['mediaFile' => $media->id]), false);

        // Both endpoints actually serve the bytes.
        $this->get(route('files.serve', ['mediaFile' => $media->id]))->assertOk();
        $this->get(route('files.download', ['mediaFile' => $media->id]))->assertOk();
    }

    public function test_a_material_whose_bytes_are_missing_says_so(): void
    {
        $media = app(\App\Services\FileUploadService::class)->uploadFile(
            UploadedFile::fake()->create('Gone.pdf', 10, 'application/pdf'),
            'lesson_materials',
            ['disk' => 'public']
        );

        LessonMaterial::create([
            'lesson_id' => $this->lesson->id,
            'media_file_id' => $media->id,
            'position' => 1,
        ]);

        Storage::disk('public')->delete($media->path);

        $this->get(
            route('student.courses.modules.lessons.show', [$this->course, $this->module, $this->lesson])
        )
            ->assertOk()
            ->assertSee('unavailable');
    }
}

/** Attempts recorded against an exam. */
function ExamAttemptCount(int $examId): int
{
    return \App\Models\ExamAttempt::where('exam_id', $examId)->count();
}