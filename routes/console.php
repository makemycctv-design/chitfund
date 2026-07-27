<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Scheduled tasks. Enable the OS cron entry documented in the README:
 *   * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
 */

// Auto-close live auctions whose server end time has elapsed.
Schedule::command('chittyfund:finalize-expired-auctions')
    ->everyMinute()
    ->withoutOverlapping();

// Daily installment reminders (due-soon + overdue) at 9am company time.
Schedule::command('chittyfund:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();
