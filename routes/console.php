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

/* Keep the API usage log bounded (see ApiUsageLog::prunable). */
Schedule::command('model:prune', ['--model' => [\App\Models\ApiUsageLog::class]])
    ->daily();
