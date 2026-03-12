<?php

use App\Jobs\ProcessAutoRenewal;
use App\Jobs\SendBehavioralTrigger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Behavioral triggers — 8am diario (via Job + Command)
Schedule::job(new SendBehavioralTrigger)->dailyAt('08:00');
Schedule::command('wellcore:behavioral-triggers')->dailyAt('08:00');

// Auto-renewal — 7am diario (via Job + Command)
Schedule::job(new ProcessAutoRenewal)->dailyAt('07:00');
Schedule::command('wellcore:auto-renewal')->dailyAt('07:00');

// Streak reset — medianoche diario (48h sin actividad → racha 0)
Schedule::command('wellcore:reset-streaks')->dailyAt('00:05');

// Weekly recap — lunes 9am (resumen de la semana anterior)
Schedule::command('wellcore:weekly-recap')->weeklyOn(1, '09:00');
