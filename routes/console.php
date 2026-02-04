<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\EnviarRecordatorioPago24hJob;
use App\Jobs\MarcarTurnosVencidosJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler (Flujo A)
|--------------------------------------------------------------------------
| 1) Marcar turnos vencidos (para no permitir pagar/cancelar fuera de tiempo)
| 2) Recordatorio de pago 24h antes si NO pagó
|--------------------------------------------------------------------------
*/

Schedule::job(new MarcarTurnosVencidosJob())
    ->everyMinute();

Schedule::job(new EnviarRecordatorioPago24hJob())
    ->everyMinute();
