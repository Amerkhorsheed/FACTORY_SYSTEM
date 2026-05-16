<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes — Scheduled Commands
|--------------------------------------------------------------------------
|
| All scheduled commands are registered here per SKILLS.md Pattern 15.
| Timezone: Asia/Damascus (configured in config/app.php).
|
*/

// ── Daily at 9:00 AM — Send overdue invoice reminders ────────
Schedule::command('factory:send-overdue-alerts')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();

// ── Every 6 hours — Check low stock levels ───────────────────
Schedule::command('factory:check-low-stock')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer();

// ── Daily at 2:00 AM — Database backup ───────────────────────
Schedule::command('factory:backup')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();
