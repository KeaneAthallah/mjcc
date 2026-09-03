<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('crawler.enabled')) {
    $sources = ['ats', 'dapo', 'sp2kp', 'bps'];
    $times = ['01:00', '01:30', '02:00', '02:30'];

    foreach ($sources as $index => $slug) {
        $time = config("crawler.schedule.{$slug}", $times[$index]);

        Schedule::command("crawler:sync --source={$slug}")
            ->dailyAt($time)
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground()
            ->name("crawler:{$slug}");
    }
}
