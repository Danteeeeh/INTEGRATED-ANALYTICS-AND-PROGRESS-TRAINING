<?php

namespace App\Services;

use App\Models\ExamAnswer;
use App\Models\Question;
use App\Models\QuizAnswer;

/**
 * Grades a single answer against the question the student was actually shown.
 *
 * Before this existed the four grading loops (quiz normal, quiz auto-submit,
 * exam service, exam controller) each re-implemented the comparison against the
 * *live* Test Bank question — so editing an answer key silently rescored
 * attempts that had already been completed, and identification questions were
 * never auto-graded at all (§2, §12, §14).
 *
 * The rule is deliberately simple: the frozen snapshot wins. What the student
 * read is what they are marked on.
 */
class QuestionGrader
{
    public function __construct(private QuestionSnapshotService $snapshots) {}

    /**
     * @param  mixed  $submitted  Raw request value: scalar, array, or null
     * @param  array<string, mixed>|null  $snapshot
     * @param  float|null  $pivotPoints  The assessment's own weighting, used only
     *                                   when neither snapshot nor question has one
     * @return array{is_correct: bool, points: float, manual: bool, max_points: float}
     */
    public function grade(mixed $submitted, ?array $snapshot, ?Question $question, ?float $pivotPoints = null): array
    {
        $type = $snapshot['question_type'] ?? $question?->question_type;
        $max = (float) ($snapshot['points']
            ?? $pivotPoints
            ?? $question?->default_points
            ?? 1);

        $max = $max > 0 ? $max : 1.0;

        // Essays are never auto-marked — §21 expects the instructor to grade them.
        if ($type === Question::TYPE_ESSAY) {
            return ['is_correct' => false, 'points' => 0.0, 'manual' => true, 'max_points' => $max];
        }

        if ($type === null) {
            // Question was hard-deleted and no snapshot exists: nothing to grade.
            return ['is_correct' => false, 'points' => 0.0, 'manual' => true, 'max_points' => $max];
        }

        // Free-text types need an answer key before they can be auto-marked.
        if (in_array($type, [Question::TYPE_IDENTIFICATION, Question::TYPE_SHORT_ANSWER], true)) {
            $submittedText = trim((string) ($submitted ?? ''));

            if ($submittedText === '') {
                // Blank submission is simply wrong, not pending.
                return ['is_correct' => false, 'points' => 0.0, 'manual' => false, 'max_points' => $max];
            }

            if (! $this->hasTextKey($snapshot, $question)) {
                // No accepted answer configured — a human must decide (§2).
                return ['is_correct' => false, 'points' => 0.0, 'manual' => true, 'max_points' => $max];
            }
        }

        $isCorrect = $this->snapshots->isCorrect($snapshot, $submitted, $question);

        return [
            'is_correct' => $isCorrect,
            'points' => $isCorrect ? $max : 0.0,
            'manual' => false,
            'max_points' => $max,
        ];
    }

    /**
     * Convenience for models that carry a snapshot of their own.
     *
     * @return array{is_correct: bool, points: float, manual: bool, max_points: float}
     */
    public function gradeAnswer(QuizAnswer|ExamAnswer $answer, mixed $submitted, ?float $pivotPoints = null): array
    {
        $snapshot = $answer->snapshotArray();

        return $this->grade($submitted, $snapshot, $answer->question, $pivotPoints);
    }

    /**
     * Type label used by the attempt views, resolved once.
     */
    public function typeLabelFor(?array $snapshot, ?Question $question): string
    {
        $type = $snapshot['question_type'] ?? $question?->question_type;

        return (string) match ($type) {
            Question::TYPE_MULTIPLE_CHOICE => 'Multiple Choice',
            Question::TYPE_MULTIPLE_ANSWER => 'Multiple Answer',
            Question::TYPE_TRUE_FALSE => 'True / False',
            Question::TYPE_IDENTIFICATION => 'Identification',
            Question::TYPE_SHORT_ANSWER => 'Short Answer',
            Question::TYPE_ESSAY => 'Essay',
            default => 'Question',
        };
    }

    private function hasTextKey(?array $snapshot, ?Question $question): bool
    {
        if ($snapshot !== null) {
            $accepted = $snapshot['accepted_answers'] ?? [];

            if (is_array($accepted) && array_filter($accepted, fn ($a) => trim((string) $a) !== '') !== []) {
                return true;
            }
        }

        if (! $question) {
            return false;
        }

        return $question->choices
            ->where('is_correct', true)
            ->contains(fn ($choice) => trim((string) $choice->choice_text) !== '');
    }
}
