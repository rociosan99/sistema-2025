<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\EnviarRecordatorios24hJob;      // confirmación asistencia
use App\Jobs\EnviarRecordatorioPago24hJob;   // recordatorio pago
//use App\Jobs\ArmarAgendaDiariaProfesorJob;   // (ejemplo) tu agenda automática

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
| 1) Confirmación de asistencia 24h antes: por turno (no a las 6)
| 2) Recordatorio de pago 24h antes: por turno (no a las 6)
| 3) Agenda automática del profesor: sí puede ir a las 06:00
*/

// ✅ 24h antes del turno: manda mail “confirmar/cancelar”
Schedule::job(new EnviarRecordatorios24hJob())
    ->everyMinute();

// ✅ 24h antes del turno si sigue pendiente de pago: manda link de pago
Schedule::job(new EnviarRecordatorioPago24hJob())
    ->everyMinute();

// ✅ Agenda automática (lo tuyo)
//Schedule::job(new ArmarAgendaDiariaProfesorJob())
    //->dailyAt('06:00');
