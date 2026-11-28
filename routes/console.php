<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Vendor Inventory module: keep the vendor activity log to the last 6 months
| (VendorActivityLog::RETENTION_MONTHS). Needs the scheduler running:
|   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
*/
Schedule::command('model:prune', [
    '--model' => [\App\Models\VendorActivityLog::class],
])->dailyAt('02:00');

/* ============ MEDIA LOCATION INSIGHTS ============
   Needs the server cron: * * * * * php artisan schedule:run
   and a worker for the "insights" queue (see deployment notes).

   Run times are set in Super Admin → API Request Manager (how often, and the
   hours they may run in). --sync does the work in this process, so the one
   cron line is all the server needs (no queue worker). Each run takes only its
   share of the day's hoardings and stays within the free daily limits. */
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever(\App\Support\ApiControl::HEARTBEAT_KEY, now()->timestamp))
    ->everyMinute()
    ->name('scheduler-heartbeat');

Schedule::command('insights:enrich --sync')
    ->cron(\App\Support\ApiControl::runCron('places'))
    ->when(fn () => \App\Support\ApiControl::autoRun('places') && \App\Support\ApiControl::inRunWindow())
    ->withoutOverlapping()
    ->onOneServer();

/* ============ HOARDING TRAFFIC (TomTom Traffic Flow) ============
   Hourly, but it only calls TomTom for hoardings that are due: no traffic
   row yet, next_refresh_at passed (every 15 days per hoarding) or moved.
   Capped by TOMTOM_TRAFFIC_DAILY_LIMIT. Runs in-process — no queue worker. */
Schedule::command('refresh:hoarding-traffic')
    ->cron(\App\Support\ApiControl::runCron('traffic'))
    ->when(fn () => \App\Support\ApiControl::autoRun('traffic') && \App\Support\ApiControl::inRunWindow())
    ->withoutOverlapping()
    ->onOneServer();

/* Keep the API usage log bounded (see ApiUsageLog::prunable). */
Schedule::command('model:prune', ['--model' => [\App\Models\ApiUsageLog::class]])
    ->daily();
