<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Jobs\EnviarRecordatorioPago24hJob;
use App\Jobs\MarcarTurnosVencidosJob;
use App\Jobs\ProcesarSolicitudesDisponibilidadJob;
use App\Jobs\ExpirarOfertasSolicitudJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Flujo A
 * - Marcar turnos vencidos
 * - Recordatorio pago 24h antes
 */
Schedule::job(new MarcarTurnosVencidosJob())
    ->everyMinute();

Schedule::job(new EnviarRecordatorioPago24hJob())
    ->everyMinute();

/**
 * Matching solicitudes -> genera ofertas
 */
Schedule::job(new ProcesarSolicitudesDisponibilidadJob())
    ->everyMinute();

/**
 * Expira ofertas pendientes vencidas
 */
Schedule::job(new ExpirarOfertasSolicitudJob())
    ->everyMinute();
