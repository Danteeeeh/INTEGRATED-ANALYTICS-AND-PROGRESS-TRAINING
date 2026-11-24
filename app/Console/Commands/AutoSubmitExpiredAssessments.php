<?php

namespace App\Console\Commands;

use App\Services\AssessmentAutoSubmitService;
use Illuminate\Console\Command;

/**
 * Submits and closes quiz and exam attempts whose own time limit or assessment
 * deadline has expired.
 *
 * Without this, a student who closes the tab never gets a result: the attempt
 * page normally drives the auto-submit, but nobody is there to run it. Wire it
 * to the standard Laravel scheduler:
 *
 *   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
 */
class AutoSubmitExpiredAssessments extends Command
{
    protected $signature = 'assessments:auto-submit-expired';

    protected $description = 'Auto-submit quiz and exam attempts whose time limit or assessment deadline has expired.';

    public function handle(AssessmentAutoSubmitService $assessments): int
    {
        $closed = $assessments->submitExpired();

        $this->info("Auto-submitted {$closed['quizzes']} quiz and {$closed['exams']} exam attempt(s).");

        return self::SUCCESS;
    }
}
