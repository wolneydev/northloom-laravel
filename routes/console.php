<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deliver due task reminders over Telegram once a minute.
Schedule::command('tasks:send-notifications')
    ->everyMinute()
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping();
