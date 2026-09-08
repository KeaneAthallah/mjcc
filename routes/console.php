<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('public_data.enabled')) {
    Schedule::command('public-data:scrape')
        ->dailyAt(config('public_data.schedule', '03:00'))
        ->withoutOverlapping()
        ->onOneServer()
        ->runInBackground()
        ->name('public-data:scrape');
}
