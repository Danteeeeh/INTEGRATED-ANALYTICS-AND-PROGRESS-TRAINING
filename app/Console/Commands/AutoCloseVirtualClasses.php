<?php

namespace App\Console\Commands;

use App\Models\VirtualClass;
use App\Services\VirtualClassSessionCloser;
use Illuminate\Console\Command;

/**
 * Closes virtual class meetings whose scheduled end time has already passed.
 *
 * The app also self-heals on page views (VirtualClass::syncStatus), so this
 * command is only needed when you want the status flipped even if nobody
 * opens the virtual class list. Wire it to cron:
 *
 *   * * * * * cd /path/to/project && php artisan virtual-classes:auto-close >> /dev/null 2>&1
 */
class AutoCloseVirtualClasses extends Command
{
    protected $signature = 'virtual-classes:auto-close';

    protected $description = 'Auto-close virtual class meetings whose end time has passed.';

    public function handle(VirtualClassSessionCloser $closer): int
    {
        $expired = VirtualClass::query()
            ->whereIn('status', [VirtualClass::STATUS_SCHEDULED, VirtualClass::STATUS_ONGOING])
            ->whereDate('meeting_date', '<=', now()->toDateString())
            ->get()
            ->filter(fn (VirtualClass $virtualClass) => $virtualClass->hasEnded());

        foreach ($expired as $virtualClass) {
            // Flip the status first so a failing LiveKit call can never leave
            // an overrun class looking live, then drop the room and close the
            // attendance rows of everyone who was forced out.
            $virtualClass->syncStatus();
            $closer->close($virtualClass);
        }

        $closed = $expired->count();

        $this->info($closed === 0
            ? 'No virtual classes needed closing.'
            : "Closed {$closed} virtual class(es).");

        return self::SUCCESS;
    }
}