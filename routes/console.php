<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('app.backup.enabled')) {
    Schedule::command('stms:backup')
        ->dailyAt('02:00')
        ->withoutOverlapping()
        ->onOneServer();
}

if (config('app.health.monitor_enabled')) {
    Schedule::command('stms:health-check')
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
