<?php

// use Illuminate\Foundation\Inspiring;
// use Illuminate\Support\Facades\Artisan;
// use Illuminate\Console\Scheduling\Schedule;
// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();
// $schedule->command('deals:deactivate-expired')->daily();
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
// use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schedule;
Schedule::command('deals:deactivate-expired')->everyMinute();
Schedule::command('deactivate-expired-coupon')->everyMinute();
