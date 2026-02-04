<?php

namespace App\Jobs;

use App\Models\Pago;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MarcarTurnosVencidosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        DB::transaction(function () {

            // Solo estados "abiertos" que podrían vencer
            $turnos = Turno::query()
                ->with('pago')
                ->whereIn('estado', [
                    Turno::ESTADO_PENDIENTE,
                    Turno::ESTADO_ACEPTADO,
                    Turno::ESTADO_PENDIENTE_PAGO,
                ])
                ->lockForUpdate()
                ->get();

            foreach ($turnos as $turno) {

                // 1) Si tiene pago aprobado, asegurar estado confirmado
                if ($turno->pago && $turno->pago->estado === Pago::ESTADO_APROBADO) {
                    if ($turno->estado !== Turno::ESTADO_CONFIRMADO) {
                        $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]);
                    }
                    continue;
                }

                // 2) Si ya terminó el turno => vence
                // (usa helper del modelo, ver abajo)
                if ($turno->finDateTime()->isPast()) {
                    $turno->update(['estado' => Turno::ESTADO_VENCIDO]);
                }
            }
        });
    }
}
