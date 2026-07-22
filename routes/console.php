<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Deliver due task reminders over Telegram once a minute.
// withoutOverlapping(5): if the process dies mid-run (e.g. Docker restart),
// the lock is released after 5 minutes so the next tick can recover overdue
// notifications without overlapping a healthy concurrent run.
Schedule::command('tasks:send-notifications')
    ->everyMinute()
    ->timezone('America/Sao_Paulo')
    ->withoutOverlapping(5);
