<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Pulse Data Cleanup Scheduling
Schedule::command('pulse:clear')->hourly();

// Optional: Restart Pulse ingestion workers daily (useful in production)
Schedule::command('pulse:restart')->dailyAt('02:00');

// Optional: Take Pulse snapshots for metrics (if needed)
Schedule::command('pulse:snapshot')->everyFiveMinutes();