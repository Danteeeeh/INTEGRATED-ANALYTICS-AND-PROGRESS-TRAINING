<?php

namespace App\Services;

use App\Models\Question;

/**
 * §10 (usage tracking) and §11 (performance analytics + recommendations).
 *
 * Every figure is derived live from quiz_questions / exam_questions and the
 * answer rows rather than from a counter column, so it cannot drift out of step
 * with the data it describes.
 *
 * §11 is explicit that analytics only *suggest*: nothing here mutates a
 * question, its status or its answer key.
 */
class QuestionUsageService
{
    /**
     * Baseline thresholds for a recommendation to be meaningful at all.
     */
    public const MIN_SAMPLES = 5;

    public const LOW_MASTERY = 50.0;

    public const HIGH_MASTERY = 90.0;

    /**
     * The questions an instructor may see, with usage figures attached.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function forUser($user)
    {
        $query = Question::query();

        // Admins report on the whole bank; instructors on theirs and on shared
        // questions only (§16).
        if (! $user->isAdmin()) {
            $query->whereHas('bank', fn ($q) => $q
                ->where('created_by', $user->id)
                ->orWhere('is_shared', true));
        }

        return $query
            ->with(['bank:id,title,course_id,created_by,is_shared', 'category:id,name,course_id'])
            ->withCount([
                'quizzes',
                'exams',
                'quizAnswers as quiz_answers_count',
                'quizAnswers as quiz_correct_count' => fn ($q) => $q->where('is_correct', true),
                'examAnswers as exam_answers_count',
                'examAnswers as exam_correct_count' => fn ($q) => $q->where('is_correct', true),
            ]);
    }

    /**
     * Usage summary for one already-loaded question.
     *
     * @return array<string, int|float|null>
     */
    public function summarise(Question $question): array
    {
        $answered = (int) $question->quiz_answers_count + (int) $question->exam_answers_count;
        $correct = (int) $question->quiz_correct_count + (int) $question->exam_correct_count;

        return [
            'used_in' => (int) $question->quizzes_count + (int) $question->exams_count,
            'quizzes' => (int) $question->quizzes_count,
            'exams' => (int) $question->exams_count,
            'answered' => $answered,
            'correct' => $correct,
            'mastery' => $answered > 0 ? round(($correct / $answered) * 100, 1) : null,
        ];
    }

    /**
     * A human-readable read on the item (§11: recommendation only).
     *
     * @param  array<string, int|float|null>  $summary
     * @return array{tone: string, label: string, message: string}
     */
    public function recommend(Question $question, array $summary): array
    {
        if ($summary['used_in'] === 0 && $summary['answered'] === 0) {
            return [
                'tone' => 'muted',
                'label' => 'Unused',
                'message' => 'Not yet placed in a quiz or exam. Add it to an assessment, or retire it if it is no longer needed.',
            ];
        }

        if ($summary['answered'] < self::MIN_SAMPLES) {
            return [
                'tone' => 'info',
                'label' => 'Gathering data',
                'message' => "Only {$summary['answered']} response(s) so far — too few to judge the item. Keep collecting before acting on it.",
            ];
        }

        $mastery = (float) $summary['mastery'];
        $difficulty = $question->difficultyLabel();

        if ($mastery < self::LOW_MASTERY) {
            $message = "Only {$mastery}% of responses are correct. Review the wording or the distractors, and check whether the topic was taught.";

            if ($question->difficulty === Question::DIFFICULTY_EASY) {
                $message .= " This is rated {$difficulty}, so the gap suggests an item or teaching problem rather than a hard topic.";
            }

            return ['tone' => 'danger', 'label' => 'Below mastery', 'message' => $message];
        }

        if ($mastery >= self::HIGH_MASTERY) {
            $message = "{$mastery}% of responses are correct. The item is discriminating poorly — consider a more challenging version.";

            if ($question->difficulty === Question::DIFFICULTY_HARD) {
                $message .= " It is rated {$difficulty}, so it is not stretching the class.";
            }

            return ['tone' => 'success', 'label' => 'Ceiling reached', 'message' => $message];
        }

        if ($question->difficulty === Question::DIFFICULTY_HARD && $mastery < 70.0) {
            return [
                'tone' => 'warning',
                'label' => 'As expected',
                'message' => "{$mastery}% correct on a {$difficulty} item — behaving as intended. No change recommended.",
            ];
        }

        return [
            'tone' => 'success',
            'label' => 'Healthy',
            'message' => "{$mastery}% correct across {$summary['answered']} responses, in the expected range for a {$difficulty} item.",
        ];
    }

    /**
     * Annotate a page of questions in place with usage and a recommendation.
     *
     * @param  iterable<int, Question>  $questions
     * @return iterable<int, Question>
     */
    public function annotate(iterable $questions): iterable
    {
        foreach ($questions as $question) {
            $summary = $this->summarise($question);

            $question->setAttribute('usage', $summary);
            $question->setAttribute('insight', $this->recommend($question, $summary));
        }

        return $questions;
    }
}
