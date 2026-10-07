<?php

namespace App\Services;

use App\Models\AssignmentSubmission;

class FeedbackSuggestionService
{
    /**
     * Draft a feedback message for an instructor from real grading data.
     * Preview only — this service never saves anything.
     */
    public function suggestForSubmission(AssignmentSubmission $submission): string
    {
        // Grades live on grade_items linked to the assignment; the morph
        // relation on AssignmentSubmission is not part of this schema.
        $scorePercent = $this->scorePercentForSubmission($submission);

        $parts = [];

        if ($scorePercent !== null) {
            $parts[] = $this->strengthSentence((float) $scorePercent);
        } else {
            $parts[] = 'Review the submission against the assignment requirements.';
        }

        $rubricNotes = $this->rubricNotes($submission);
        if ($rubricNotes !== '') {
            $parts[] = $rubricNotes;
        }

        $parts[] = $this->nextStepSentence((float) ($scorePercent ?? 0));

        $studentName = $submission->student?->full_name ?? 'the student';
        $assignmentTitle = $submission->assignment?->title ?? 'the assignment';

        return 'Hi '.$studentName.', here is my feedback on "'.$assignmentTitle.'": '."\n\n"
            .implode("\n\n", $parts)."\n\n"
            .'Keep up the good work and review the suggested items before the next submission.';
    }

    protected function scorePercentForSubmission(AssignmentSubmission $submission): ?float
    {
        $item = \App\Models\GradeItem::where('class_id', $submission->assignment?->class_id)
            ->where('related_type', \App\Models\Assignment::class)
            ->where('related_id', $submission->assignment_id)
            ->first();

        if (! $item) {
            return null;
        }

        $grade = $item->grades()
            ->where('student_id', $submission->student_id)
            ->first();

        return $grade?->score_percent !== null ? (float) $grade->score_percent : null;
    }

    protected function strengthSentence(float $scorePercent): string
    {
        if ($scorePercent >= 90) {
            return 'Excellent work — you clearly understand the material and your submission meets the highest expectations.';
        }

        if ($scorePercent >= 75) {
            return 'Good work — your submission covers the key points well and shows a solid grasp of the topic.';
        }

        if ($scorePercent >= 60) {
            return 'Your submission shows effort and some strong points, but there are a few areas to tighten up.';
        }

        return 'This submission is a start, but several areas need more work before it fully meets the requirements.';
    }

    protected function rubricNotes(AssignmentSubmission $submission): string
    {
        $assessments = $submission->rubricAssessments()->with(['criterion', 'level'])->get();

        if ($assessments->isEmpty()) {
            return '';
        }

        $lines = $assessments->map(function ($assessment) {
            $criterion = $assessment->criterion?->name ?? 'Criterion';
            $level = $assessment->level?->name ?? 'not assessed';

            return '• '.$criterion.': '.$level;
        });

        return 'Rubric summary:'."\n".$lines->implode("\n");
    }

    protected function nextStepSentence(float $scorePercent): string
    {
        if ($scorePercent >= 90) {
            return 'Consider attempting the next challenge or helping a classmate review the same topic.';
        }

        if ($scorePercent >= 75) {
            return 'To push higher, double-check the details and review the rubric criteria you scored lower on.';
        }

        if ($scorePercent >= 60) {
            return 'Re-read the core lesson, try one more practice attempt, and resubmit when ready.';
        }

        return 'Start by reviewing the core lessons, complete the suggested learning-plan items, then attempt this again.';
    }
}
