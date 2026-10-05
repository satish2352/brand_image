<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/* ============ MEDIA LOCATION INSIGHTS ============
   Needs the server cron: * * * * * php artisan schedule:run
   and a worker for the "insights" queue (see deployment notes).

   Hourly small batches rather than one big nightly run: spreads requests
   across the day, and a new or moved hoarding is picked up within the hour.
   The command queues no more than the day's remaining credits allow. */
Schedule::command('insights:enrich')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

/* ============ HOARDING TRAFFIC (TomTom Traffic Flow) ============
   Hourly, but it only calls TomTom for hoardings that are due: no traffic
   row yet, next_refresh_at passed (every 15 days per hoarding) or moved.
   Capped by TOMTOM_TRAFFIC_DAILY_LIMIT. Runs in-process — no queue worker. */
Schedule::command('refresh:hoarding-traffic')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

/* ============ ROADSTAR AUDIENCE ============
   Once a day (weekdays only by default — the RoadStar test server is up
   Mon–Fri 10:00–20:00). It only queues mapped hoardings whose last sync is
   older than 15 days (or that were never synced), at most
   ROADSTAR_MAX_PER_RUN per run; a worker for the "roadstar" queue sends them
   ROADSTAR_BATCH_SIZE sites per request. */
$roadStarSync = Schedule::command('roadstar:sync')
    ->dailyAt((string) config('services.roadstar.schedule_at', '11:00'))
    ->withoutOverlapping()
    ->onOneServer();
if (config('services.roadstar.schedule_weekdays_only', true)) {
    $roadStarSync->weekdays();
}

/* Keep the API usage log bounded (see ApiUsageLog::prunable). */
Schedule::command('model:prune', ['--model' => [\App\Models\ApiUsageLog::class]])
    ->daily();
