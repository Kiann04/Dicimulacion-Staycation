<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Marking finished stays as completed used to happen while rendering the admin
 * dashboard, so a GET request mutated booking rows and the sweep only ran when
 * somebody opened the page. It is a scheduled command instead; on shared hosting
 * this needs only the standard cron entry running `schedule:run`.
 */
Schedule::command('bookings:complete-past')
    ->dailyAt('01:00')
    ->withoutOverlapping();
