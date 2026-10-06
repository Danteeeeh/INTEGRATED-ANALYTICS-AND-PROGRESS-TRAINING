<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionChoice;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * §5, §13 and §14 of the Test Bank spec.
 *
 * The contract being protected here is: once a student has opened an assessment,
 * the wording they were shown is frozen. Editing, retiring or trashing the
 * original Test Bank question may change the bank, but it may never change what
 * that attempt shows or how it was scored.
 */
class QuestionSnapshotTest extends TestCase
{
    use CreatesLmsUsers;

    /**
     * An instructor, an enrolled student, one bank and a one-question quiz.
     *
     * @param  array<string, mixed>  $question
     * @param  list<array{text: string, correct?: bool}>  $choices
     * @return array<string, mixed>
     */
    private function scenario(array $question = [], array $choices = [], ?float $quizPoints = null): array
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser('student');

        $course = Course::factory()->create(['status' => 'published', 'created_by' => $instructor->id]);
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        $bank = QuestionBank::factory()->create([
            'course_id' => $course->id,
            'created_by' => $instructor->id,
        ]);

        $model = Question::factory()->create(array_merge([
            'question_bank_id' => $bank->id,
            'question_text' => 'Original wording?',
            'default_points' => 1,
            'created_by' => $instructor->id,
            'status' => Question::STATUS_ACTIVE,
        ], $question));

        foreach ($choices as $index => $choice) {
            QuestionChoice::factory()->create([
                'question_id' => $model->id,
                'choice_text' => $choice['text'],
                'is_correct' => $choice['correct'] ?? false,
                'position' => $index + 1,
                'points' => 1,
            ]);
        }

        $quiz = Quiz::create([
            'class_id' => $class->id,
            'title' => 'Snapshot quiz',
            'slug' => 'snapshot-'.uniqid(),
            'status' => Quiz::STATUS_PUBLISHED,
            'created_by' => $instructor->id,
        ]);

        $quiz->questions()->attach($model->id, [
            'position' => 1,
            'points' => $quizPoints ?? $model->default_points,
        ]);

        return compact('instructor', 'student', 'course', 'class', 'bank', 'model', 'quiz');
    }

    /**
     * @param  array<string, mixed>  $s
     */
    private function startAttempt(array $s): QuizAttempt
    {
        $response = $this->actingAs($s['student'])->get(
            route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']])
        );

        $response->assertOk();

        $attempt = QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $s['student']->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertNotNull($attempt->answers()->first(), 'Attempt was opened without any answer rows.');

        return $attempt;
    }

    /**
     * @param  array<string, mixed>  $s
     * @param  array<int|string, mixed>  $answers
     */
    private function submit(array $s, array $answers): void
    {
        $this->actingAs($s['student'])->post(
            route('student.courses.quizzes.attempt.store', [$s['course'], $s['quiz']]),
            ['answers' => $answers]
        )->assertRedirect();
    }

    public function test_opening_an_attempt_freezes_the_question_as_offered(): void
    {
        $s = $this->scenario(
            ['question_text' => 'What does CPU stand for?', 'default_points' => 1],
            [
                ['text' => 'Central Processing Unit', 'correct' => true],
                ['text' => 'Computer Personal Unit', 'correct' => false],
            ]
        );

        $attempt = $this->startAttempt($s);
        $answer = $attempt->answers()->first();

        $snapshot = $answer->snapshotArray();

        $this->assertNotNull($snapshot, 'No snapshot was recorded when the attempt opened.');
        $this->assertSame('What does CPU stand for?', $snapshot['question_text']);
        $this->assertSame(Question::TYPE_MULTIPLE_CHOICE, $snapshot['question_type']);
        $this->assertCount(2, $snapshot['choices']);
        $this->assertSame('Central Processing Unit', $snapshot['accepted_answers'][0]);
        $this->assertNotEmpty($snapshot['captured_at']);
    }

    public function test_editing_the_bank_question_does_not_rewrite_a_finished_attempt(): void
    {
        $s = $this->scenario(
            ['question_text' => 'What does CPU stand for?'],
            [
                ['text' => 'Central Processing Unit', 'correct' => true],
                ['text' => 'Computer Personal Unit', 'correct' => false],
            ]
        );

        $attempt = $this->startAttempt($s);

        $this->submit($s, [
            $s['model']->id => QuestionChoice::where('question_id', $s['model']->id)->first()->id,
        ]);

        // The instructor rewords the question and rewrites the answer key.
        $s['model']->update([
            'question_text' => 'Entirely new wording now.',
        ]);

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempts.show', [$s['course'], $s['quiz'], $attempt]))
            ->assertOk()
            ->assertSee('What does CPU stand for?', false)
            ->assertDontSee('Entirely new wording now.', false);
    }

    public function test_trashing_the_bank_question_still_renders_the_original(): void
    {
        $s = $this->scenario(
            ['question_text' => 'Name the memory closest to the CPU.'],
            [['text' => 'Cache', 'correct' => true]]
        );

        $attempt = $this->startAttempt($s);

        $this->submit($s, [$s['model']->id => QuestionChoice::first()->id]);

        // Question uses SoftDeletes; the belongsTo relation returns null for a
        // trashed model, which used to blank the whole review to "Question".
        $s['model']->delete();

        $this->assertTrue($s['model']->trashed());
        $this->assertNull(Question::find($s['model']->id), 'A trashed question must not resolve through find().');

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempts.show', [$s['course'], $s['quiz'], $attempt]))
            ->assertOk()
            ->assertSee('Name the memory closest to the CPU.', false);
    }

    public function test_multiple_choice_is_marked_correctly(): void
    {
        $s = $this->scenario(
            ['question_text' => 'Pick the odd one out.'],
            [
                ['text' => 'Apple', 'correct' => false],
                ['text' => 'Banana', 'correct' => true],
            ]
        );

        $correctChoice = QuestionChoice::where('question_id', $s['model']->id)->where('is_correct', true)->first();
        $wrongChoice = QuestionChoice::where('question_id', $s['model']->id)->where('is_correct', false)->first();

        $attempt = $this->startAttempt($s);
        $this->submit($s, [$s['model']->id => $correctChoice->id]);

        $attempt->refresh();
        $this->assertTrue($attempt->answers()->first()->is_correct);
        $this->assertSame(1.0, (float) $attempt->answers()->first()->points_awarded);
        $this->assertSame(100.0, round((float) $attempt->score_percent, 1));
        $this->assertTrue($attempt->is_passed);

        // A second student answers incorrectly.
        $other = $this->makeUser('student');
        Enrollment::create(['student_id' => $other->id, 'class_id' => $s['class']->id, 'status' => 'active']);

        $this->actingAs($other)->get(
            route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']])
        )->assertOk();

        $second = QuizAttempt::where('quiz_id', $s['quiz']->id)
            ->where('student_id', $other->id)
            ->firstOrFail();

        $this->actingAs($other)->post(
            route('student.courses.quizzes.attempt.store', [$s['course'], $s['quiz']]),
            ['answers' => [$s['model']->id => $wrongChoice->id]]
        )->assertRedirect();

        $second->refresh();
        $this->assertFalse($second->answers()->first()->is_correct);
        $this->assertSame(0.0, (float) $second->answers()->first()->points_awarded);
        $this->assertSame(0.0, round((float) $second->score_percent, 1));
    }

    public function test_snapshot_uses_the_quiz_weighting_not_the_bank_default(): void
    {
        $s = $this->scenario(
            ['default_points' => 1],
            [['text' => 'Only choice', 'correct' => true]],
            5.0 // the quiz weights this question at 5
        );

        $attempt = $this->startAttempt($s);
        $answer = $attempt->answers()->first();

        // JSON round-trips 5.0 as an int, so compare numerically.
        $this->assertSame(5.0, (float) $answer->snapshotArray()['points']);

        $this->submit($s, [$s['model']->id => QuestionChoice::first()->id]);

        // The bank's own default_points must not leak into the assessment score.
        $this->assertSame(5.0, (float) $attempt->refresh()->answers()->first()->points_awarded);
        $this->assertSame(100.0, round((float) $attempt->refresh()->score_percent, 1));
    }

    public function test_identification_auto_marks_without_the_case_flag(): void
    {
        $s = $this->scenario(
            [
                'question_type' => Question::TYPE_IDENTIFICATION,
                'question_text' => 'Name the process of a program running.',
                'is_case_sensitive' => false,
            ],
            [['text' => 'Execution', 'correct' => true]]
        );

        $attempt = $this->startAttempt($s);

        $this->submit($s, [$s['model']->id => 'execution']);

        $answer = $attempt->refresh()->answers()->first();

        $this->assertTrue($answer->is_correct, 'Case-insensitive identification should accept "execution".');
        $this->assertSame(1.0, (float) $answer->points_awarded);
    }

    public function test_identification_honours_the_case_sensitivity_flag(): void
    {
        $s = $this->scenario(
            [
                'question_type' => Question::TYPE_IDENTIFICATION,
                'question_text' => 'Name the process of a program running.',
                'is_case_sensitive' => true,
            ],
            [['text' => 'Execution', 'correct' => true]]
        );

        $attempt = $this->startAttempt($s);

        $this->submit($s, [$s['model']->id => 'execution']);

        $answer = $attempt->refresh()->answers()->first();

        $this->assertFalse($answer->is_correct, 'Case-sensitive identification must reject "execution".');
        $this->assertSame(0.0, (float) $answer->points_awarded);
    }

    public function test_identification_without_an_answer_key_is_left_for_the_instructor(): void
    {
        $s = $this->scenario([
            'question_type' => Question::TYPE_IDENTIFICATION,
            'question_text' => 'Describe the fetch-decode-execute cycle.',
            'is_case_sensitive' => false,
        ]);

        $attempt = $this->startAttempt($s);

        $this->submit($s, [$s['model']->id => 'Some thoughtful answer']);

        $answer = $attempt->refresh()->answers()->first();

        $this->assertNull($answer->is_correct, 'No configured answer key means no automatic verdict.');
        $this->assertNull($answer->points_awarded, 'An ungraded answer must stay pending, not zero.');
        $this->assertNull($attempt->is_passed, 'A pass/fail verdict cannot be reached while work is unmarked.');
    }

    public function test_essay_is_excluded_from_the_score_until_an_instructor_grades_it(): void
    {
        $s = $this->scenario(
            ['question_type' => Question::TYPE_ESSAY, 'question_text' => 'Explain virtual memory.'],
            [['text' => 'unused but present', 'correct' => true]]
        );

        $attempt = $this->startAttempt($s);

        $this->submit($s, [$s['model']->id => 'A long-form answer.']);

        $answer = $attempt->refresh()->answers()->first();

        $this->assertNull($answer->points_awarded, 'Essays are never auto-marked (§21).');

        // Counting the essay as zero would score the student 0% for work nobody
        // has read, so it is removed from both numerator and denominator.
        $this->assertSame(0.0, round((float) $attempt->score_percent, 1));
        $this->assertNull($attempt->is_passed);
    }

    public function test_archived_questions_are_no_longer_offered_for_new_attempts(): void
    {
        $s = $this->scenario(['question_text' => 'Retired question?'], [['text' => 'A', 'correct' => true]]);

        // Second question in the same quiz, already retired.
        $retired = Question::factory()->create([
            'question_bank_id' => $s['bank']->id,
            'question_text' => 'This one was retired.',
            'created_by' => $s['instructor']->id,
            'status' => Question::STATUS_ARCHIVED,
        ]);
        QuestionChoice::factory()->create([
            'question_id' => $retired->id,
            'choice_text' => 'B',
            'is_correct' => true,
            'position' => 1,
        ]);
        $s['quiz']->questions()->attach($retired->id, ['position' => 2, 'points' => 1]);

        $attempt = $this->startAttempt($s);

        $served = $attempt->answers()->pluck('question_id')->all();

        $this->assertSame([$s['model']->id], $served, 'Only live questions should be served to a student.');
        $this->assertNotContains($retired->id, $served);
    }

    public function test_a_fully_retired_quiz_refuses_to_start(): void
    {
        $s = $this->scenario(['question_text' => 'Only question'], [['text' => 'A', 'correct' => true]]);

        $s['model']->update(['status' => Question::STATUS_ARCHIVED]);

        $this->actingAs($s['student'])
            ->get(route('student.courses.quizzes.attempt.start', [$s['course'], $s['quiz']]))
            ->assertRedirect();

        $this->assertSame(
            0,
            QuizAttempt::where('quiz_id', $s['quiz']->id)->where('student_id', $s['student']->id)->count(),
            'A quiz with nothing left to serve must not open an attempt.'
        );
    }

    public function test_snapshot_uses_the_frozen_choice_set_even_if_choices_change(): void
    {
        $s = $this->scenario(
            ['question_text' => 'Capital of France?'],
            [
                ['text' => 'Paris', 'correct' => true],
                ['text' => 'Lyon', 'correct' => false],
            ]
        );

        $attempt = $this->startAttempt($s);
        $answer = $attempt->answers()->first();
        $before = $answer->renderedChoices()->pluck('text')->all();

        // The instructor swaps the options around after the exam opened.
        QuestionChoice::where('question_id', $s['model']->id)->update(['is_correct' => false]);
        QuestionChoice::where('question_id', $s['model']->id)
            ->orderByDesc('position')
            ->first()
            ->update(['choice_text' => 'Marseille', 'is_correct' => true]);

        $answer->refresh();

        $this->assertSame($before, $answer->renderedChoices()->pluck('text')->all());
        $this->assertSame('Capital of France?', $answer->renderedQuestionText());
    }
}
