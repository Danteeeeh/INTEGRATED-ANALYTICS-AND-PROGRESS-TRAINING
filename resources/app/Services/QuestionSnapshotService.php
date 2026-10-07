<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * Freezes a question exactly as it looked when an attempt was opened.
 *
 * §14 of the Test Bank spec: once a student has sat an assessment, editing the
 * Test Bank question must not change what their attempt shows. Both
 * `quiz_answers.question_id` and `exam_answers.question_id` are cascade deletes
 * and the attempt views read `$answer->question` live, so without this an
 * instructor edit silently rewrote every past attempt — and deleting the question
 * destroyed the student's answers outright.
 *
 * The snapshot is captured when the answer row is created, so it also covers the
 * window where a question is edited while an attempt is still in progress: the
 * student finishes on the wording they started with.
 */
class QuestionSnapshotService
{
    /**
     * Build the frozen payload for one question.
     *
     * @param  float|null  $points  The assessment's own weighting for this
     *                              question (quiz_questions.points), which may
     *                              differ from the bank's default_points.
     * @return array<string, mixed>
     */
    public function capture(Question $question, ?float $points = null): array
    {
        $question->loadMissing('choices');

        $choices = $question->choices->values();

        // "A", "B", "C"... follows the stored position, which is what the
        // instructor sees in the editor and what students are told to pick.
        $lettered = $choices->map(fn ($choice, $index) => [
            'id' => $choice->id,
            'key' => $this->keyFor($index),
            'text' => (string) $choice->choice_text,
            'points' => (float) ($choice->points ?? 0),
            'is_correct' => (bool) $choice->is_correct,
            'position' => (int) $choice->position,
            'feedback' => $choice->feedback,
        ])->all();

        $correctIds = collect($lettered)
            ->where('is_correct', true)
            ->pluck('id')
            ->values()
            ->all();

        // Free-text types key off the accepted wording, not a choice id: for
        // identification the "choices" are really the accepted answers (§2).
        $textKeyed = in_array($question->question_type, [
            Question::TYPE_IDENTIFICATION,
            Question::TYPE_SHORT_ANSWER,
        ], true);

        $acceptedAnswers = collect($lettered)
            ->where('is_correct', true)
            ->pluck('text')
            ->values()
            ->all();

        return [
            'question_id' => $question->id,
            'question_text' => (string) $question->question_text,
            'question_type' => $question->question_type,
            'explanation' => $question->explanation,
            'difficulty' => $question->difficulty,
            'points' => round($points ?? (float) $question->default_points, 2),
            'is_case_sensitive' => (bool) $question->is_case_sensitive,
            'choices' => $lettered,
            // Single value for one-answer types, list for multiple answer and
            // for text-keyed types (where the list is the accepted wording).
            'correct_answer' => $textKeyed
                ? $acceptedAnswers
                : ($question->question_type === Question::TYPE_MULTIPLE_ANSWER
                    ? $correctIds
                    : ($correctIds[0] ?? null)),
            'accepted_answers' => $acceptedAnswers,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Capture a whole set, keyed by question id for O(1) lookup in the loops
     * that create answer rows.
     *
     * @param  iterable<int, Question>  $questions
     * @param  array<int, float|null>  $pointsByQuestionId
     * @return array<int, array<string, mixed>>
     */
    public function captureMany(iterable $questions, array $pointsByQuestionId = []): array
    {
        $snapshots = [];

        foreach ($questions as $question) {
            $snapshots[$question->id] = $this->capture(
                $question,
                $pointsByQuestionId[$question->id] ?? null
            );
        }

        return $snapshots;
    }

    /**
     * Read a snapshot, tolerating malformed or legacy JSON.
     *
     * @return array<string, mixed>|null
     */
    public function read(mixed $snapshot): ?array
    {
        if (is_array($snapshot)) {
            return $snapshot;
        }

        if (! is_string($snapshot) || $snapshot === '') {
            return null;
        }

        $decoded = json_decode($snapshot, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }

    /**
     * Question text to display for an answer row — the frozen wording when one
     * exists, otherwise the live question (which is every pre-migration row).
     *
     * @param  array<string, mixed>|null  $snapshot
     */
    public function textFor(?array $snapshot, ?Question $question): string
    {
        if (isset($snapshot['question_text'])) {
            return (string) $snapshot['question_text'];
        }

        return (string) ($question?->question_text ?? 'Question');
    }

    /**
     * Choices to display, normalised so views never need to know which source
     * they came from.
     *
     * @return Collection<int, array{id: int, key: string, text: string, is_correct: bool}>
     */
    public function choicesFor(?array $snapshot, ?Question $question): Collection
    {
        if (! empty($snapshot['choices']) && is_array($snapshot['choices'])) {
            return collect($snapshot['choices'])->map(fn ($choice) => [
                'id' => (int) ($choice['id'] ?? 0),
                'key' => (string) ($choice['key'] ?? ''),
                'text' => (string) ($choice['text'] ?? ''),
                'is_correct' => (bool) ($choice['is_correct'] ?? false),
            ]);
        }

        return collect($question?->choices ?? [])->map(fn ($choice, $index) => [
            'id' => (int) $choice->id,
            'key' => $this->keyFor($index),
            'text' => (string) $choice->choice_text,
            'is_correct' => (bool) $choice->is_correct,
        ]);
    }

    /**
     * Correct-answer lookup that prefers the snapshot, so an edited answer key
     * cannot rescore an already-graded attempt (§14).
     *
     * @param  array<string, mixed>|null  $snapshot
     */
    public function isCorrect(?array $snapshot, mixed $submittedAnswer, ?Question $question): bool
    {
        if ($snapshot !== null && array_key_exists('correct_answer', $snapshot)) {
            return $this->matches(
                $snapshot['correct_answer'],
                $snapshot['question_type'] ?? null,
                $submittedAnswer,
                (bool) ($snapshot['is_case_sensitive'] ?? false)
            );
        }

        if (! $question) {
            return false;
        }

        $correct = $question->choices->where('is_correct', true);

        if ($question->question_type === Question::TYPE_MULTIPLE_ANSWER) {
            return $this->matches($correct->pluck('id')->all(), $question->question_type, $submittedAnswer, (bool) $question->is_case_sensitive);
        }

        return $this->matches($correct->first()?->id, $question->question_type, $submittedAnswer, (bool) $question->is_case_sensitive);
    }

    /**
     * @param  mixed  $expected
     * @param  mixed  $submitted
     */
    private function matches($expected, ?string $type, $submitted, bool $caseSensitive = false): bool
    {
        if ($type === Question::TYPE_IDENTIFICATION || $type === Question::TYPE_SHORT_ANSWER) {
            return $this->matchesText($expected, $submitted, $caseSensitive);
        }

        if (is_array($expected)) {
            $expected = array_map('intval', $expected);
            $submitted = is_array($submitted)
                ? array_map('intval', $submitted)
                : array_filter((array) $submitted, 'is_numeric');

            sort($expected);
            sort($submitted);

            return $expected !== [] && $expected === $submitted;
        }

        if ($expected === null) {
            return false;
        }

        return (int) $expected === (int) $submitted;
    }

    /**
     * Free-text matching. Case-insensitive by default: "CPU" and "cpu" are the
     * same fact, and the instructor opts in to strictness via
     * questions.is_case_sensitive.
     */
    private function matchesText(mixed $expected, mixed $submitted, bool $caseSensitive = false): bool
    {
        $accepted = is_array($expected) ? $expected : [$expected];

        $given = trim((string) $submitted);

        if ($given === '' || $accepted === []) {
            return false;
        }

        foreach ($accepted as $answer) {
            $candidate = trim((string) $answer);

            if ($candidate === '') {
                continue;
            }

            $matches = $caseSensitive
                ? $candidate === $given
                : strcasecmp($candidate, $given) === 0;

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    private function keyFor(int $index): string
    {
        // 0 -> A ... 25 -> Z, then AA, AB... so long choice lists stay addressable.
        $key = '';
        $n = $index;

        do {
            $key = chr(65 + ($n % 26)).$key;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);

        return $key;
    }
}
