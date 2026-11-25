<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('hh:parse --pages=1 --per-page=100')
    ->everyFiveMinutes()
    ->withoutOverlapping(15)
    ->appendOutputTo(storage_path('logs/hh-parser.log'));

// Scheduler heartbeat
Schedule::command('hh:scheduler-heartbeat')
    ->everyMinute()
    ->withoutOverlapping(2);
