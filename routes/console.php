<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-overdue-reminders')->dailyAt('08:00');
Schedule::command('app:notify-wishlist-availability')->everyTenMinutes();
Schedule::command('app:remove-old-notifications')->daily();
Schedule::command('app:remove-old-borrow-requests')->daily();
