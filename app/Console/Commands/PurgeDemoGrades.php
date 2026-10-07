<?php

namespace App\Console\Commands;

use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes the fabricated gradebook that DemoDataSeeder writes.
 *
 * The seeder invents grade items titled "Midterm Exam", "Final Exam",
 * "Assignment Average" and "Class Participation" with fixed percentages
 * (74/80/88/90) and grades the student 80% across the board — but it never
 * creates a single Exam record. Students therefore see exam results for exams
 * that do not exist.
 *
 * Rows are identified by the seeder's own fingerprint rather than by title, so
 * a genuine grade item that happens to be called "Final Exam" is never touched.
 *
 * Dry-run by default; pass --apply to write.
 */
class PurgeDemoGrades extends Command
{
    protected $signature = 'lms:purge-demo-grades
                            {--apply : Actually delete the fabricated rows (default is a dry run)}
                            {--keep-items : Delete the grades but leave the grade items in place}
                            {--quiz-attempts : Also remove fabricated 100% quiz attempts (see the warning)}';

    protected $description = 'Remove fabricated demo gradebook rows (no backing exam, quiz or assignment)';

    /**
     * The exact feedback string DemoDataSeeder stamps on every grade it creates.
     * No real instructor writes this, so it is a reliable marker.
     */
    private const MARKER = 'Keep up the good work. Review the areas where you scored below target.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $keepItems = (bool) $this->option('keep-items');
        $quizAttempts = (bool) $this->option('quiz-attempts');

        $this->purgeGrades($apply, $keepItems);

        if ($quizAttempts) {
            $this->purgeQuizAttempts($apply);
        } else {
            $this->newLine();
            $this->line('Quiz Performance attempts were left alone. Add --quiz-attempts to review them.');
        }

        return self::SUCCESS;
    }

    /** Remove the fabricated gradebook rows and the items that only carry them. */
    private function purgeGrades(bool $apply, bool $keepItems): void
    {
        $grades = Grade::query()
            ->where('feedback', self::MARKER)
            ->with('item.class.course')
            ->get();

        if ($grades->isEmpty()) {
            $this->info('No fabricated demo grades found.');

            return;
        }

        $this->line(sprintf('Found %d fabricated grade(s).', $grades->count()));
        $this->newLine();

        $byCourse = [];

        foreach ($grades as $grade) {
            $course = $grade->item?->class?->course?->title ?? '(no course)';

            $byCourse[$course][] = sprintf(
                '%s = %s%%',
                $grade->item?->title ?? '(no item)',
                rtrim(rtrim((string) $grade->score_percent, '0'), '.')
            );
        }

        foreach ($byCourse as $course => $entries) {
            $this->line("  {$course}");
            $this->line('      '.implode(', ', $entries));
        }

        $this->newLine();

        // Items that exist only to carry these grades can go too.
        $itemIds = $grades->pluck('grade_item_id')->filter()->unique();

        $itemCount = 0;

        if (! $keepItems && $itemIds->isNotEmpty()) {
            $itemCount = GradeItem::whereIn('id', $itemIds)
                ->whereDoesntHave('grades')
                ->count();
        }

        $gradeCount = $grades->count();

        if (! $apply) {
            $this->warn("Dry run — would delete {$gradeCount} grade(s) and {$itemCount} grade item(s).");
            $this->warn('Re-run with --apply to write. Add --keep-items to preserve the grade items.');

            return;
        }

        DB::transaction(function () use ($grades, $itemIds, $keepItems) {
            foreach ($grades as $grade) {
                $grade->delete();
            }

            if ($keepItems || $itemIds->isEmpty()) {
                return;
            }

            GradeItem::whereIn('id', $itemIds)
                ->whereDoesntHave('grades')
                ->delete();
        });

        $this->info("Deleted {$gradeCount} fabricated grade(s)"
            .($keepItems ? ' (grade items kept).' : " and up to {$itemCount} grade item(s)."));

        $this->line('Any real grades you have entered are untouched.');
    }

    /**
     * Remove the perfect-score quiz attempts DemoDataSeeder fabricates.
     *
     * The seeder records score_percent 100 with every single answer marked
     * correct, which is what fills the "Quiz Performance" chart with identical
     * full-height bars.
     *
     * These are reported rather than deleted unless explicitly requested,
     * because a genuine flawless attempt would look identical.
     */
    private function purgeQuizAttempts(bool $apply): void
    {
        $attempts = QuizAttempt::where('score_percent', 100)
            ->where('status', 'graded')
            ->whereHas('answers')
            ->with('quiz', 'student')
            ->get()
            ->filter(fn ($a) => $a->answers->isNotEmpty()
                && $a->answers->every(fn ($ans) => (bool) $ans->is_correct));

        if ($attempts->isEmpty()) {
            $this->newLine();
            $this->info('No fabricated 100% quiz attempts found.');

            return;
        }

        $this->newLine();
        $this->line(sprintf('Fabricated 100%% quiz attempts (%d):', $attempts->count()));

        foreach ($attempts->take(25) as $attempt) {
            $this->line(sprintf(
                '  %s — %s (student #%s)',
                $attempt->quiz?->title ?? 'Quiz',
                $attempt->student?->email ?? 'unknown',
                $attempt->student_id
            ));
        }

        if ($attempts->count() > 25) {
            $this->line(sprintf('  ... and %d more.', $attempts->count() - 25));
        }

        $this->newLine();
        $this->warn('These match "every answer correct". A genuine perfect score would too,');
        $this->warn('so read the list above before deleting. Re-run with --quiz-attempts to remove them.');

        if (! $apply) {
            return;
        }

        DB::transaction(function () use ($attempts) {
            foreach ($attempts as $attempt) {
                $answerIds = $attempt->answers->pluck('id');

                if ($answerIds->isNotEmpty()) {
                    DB::table('quiz_answer_choices')
                        ->whereIn('quiz_answer_id', $answerIds)
                        ->delete();
                }

                QuizAnswer::where('quiz_attempt_id', $attempt->id)->delete();
                $attempt->delete();
            }
        });

        $this->info('Deleted '.$attempts->count().' fabricated quiz attempt(s) and their answers.');
    }
}