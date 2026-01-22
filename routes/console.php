<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\EnviarRecordatorios24hJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * ✅ Scheduler (Cron de Laravel)
 * Esto hace que todos los días a las 06:00 se envíen los recordatorios
 * para los turnos "aceptado" de mañana (24hs antes).
 */
Schedule::job(new EnviarRecordatorios24hJob())->dailyAt('06:00');
