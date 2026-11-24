<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Vendor Inventory module: keep the vendor activity log to the last 6 months
| (VendorActivityLog::RETENTION_MONTHS). Needs the scheduler running:
|   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
*/
\Illuminate\Support\Facades\Schedule::command('model:prune', [
    '--model' => [\App\Models\VendorActivityLog::class],
])->dailyAt('02:00');
