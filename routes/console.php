<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Close virtual classes whose end time has passed. The app also self-heals on
// page views, but without this the status (and a LiveKit room) can stay open
// when nobody opens the class list. Requires the standard Laravel scheduler
// cron entry on the server.
Schedule::command('virtual-classes:auto-close')
    ->everyMinute()
    ->withoutOverlapping();

// Close quiz and exam attempts whose own time limit or assessment deadline ran
// out while the student was away. The attempt pages self-submit while open;
// this covers the tab that was closed before the clock reached zero, and
// untimed attempts whose assessment deadline has already passed.
Schedule::command('assessments:auto-submit-expired')
    ->everyMinute()
    ->withoutOverlapping();
