<?php

namespace Database\Seeders;

use App\Models\AaSyncLog;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds a small, repeatable AA sync history so the Super Admin dashboard can
 * reproduce its five-row “Showing 5 of N” cap hint on a fresh database.
 *
 * Rows are keyed by their deterministic counters, so running the seeder again
 * updates the same six runs instead of growing the history indefinitely.
 */
class AaSyncLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@lms.local')->first();
        $entities = ['departments', 'programs', 'sections', 'students', 'faculty', 'classes', 'enrollments'];

        foreach (range(1, 6) as $run) {
            $status = $run === 3
                ? AaSyncLog::STATUS_FAILED
                : AaSyncLog::STATUS_SUCCESS;

            $summary = [];
            foreach ($entities as $entity) {
                $summary[$entity] = [
                    'processed' => 2,
                    'created' => $run === 3 ? 0 : 1,
                    'updated' => 1,
                    'skipped' => 0,
                    'failed' => $run === 3 && $entity === 'students' ? 1 : 0,
                ];
            }

            AaSyncLog::updateOrCreate(
                [
                    'performed_by' => $admin?->id,
                    'records_processed' => 40 + ($run * 3),
                    'records_created' => $run,
                ],
                [
                    'entities' => $entities,
                    'status' => $status,
                    'records_updated' => 8 - $run,
                    'records_skipped' => $run % 2,
                    'records_failed' => $status === AaSyncLog::STATUS_FAILED ? 1 : 0,
                    'summary' => $summary,
                    'error_message' => $status === AaSyncLog::STATUS_FAILED
                        ? 'Demo seed: simulated AA connection timeout.'
                        : null,
                    'started_at' => now()->subDays(7 - $run)->setTime(8, 0),
                    'finished_at' => now()->subDays(7 - $run)->setTime(8, 5),
                ]
            );
        }

        $this->command?->info('AaSyncLogSeeder: '.AaSyncLog::count().' sync runs available.');
    }
}
