<?php

namespace App\Jobs;

use App\Mail\RecordatorioPagoTurno;
use App\Models\Pago;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EnviarRecordatorioPago24hJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Ventana: turnos cuyo inicio está entre 24h y 24h+1min (porque corre cada minuto)
        $desde = Carbon::now()->addHours(24);
        $hasta = Carbon::now()->addHours(24)->addMinute();

        // Turnos que deberían pagar (pendiente_pago) y están cerca de 24h
        $turnos = Turno::query()
            ->with(['alumno', 'profesor', 'materia', 'pago'])
            ->where('estado', Turno::ESTADO_PENDIENTE_PAGO)
            ->get()
            ->filter(function (Turno $turno) use ($desde, $hasta) {
                // inicio del turno
                $inicio = Carbon::parse($turno->fecha->format('Y-m-d') . ' ' . $turno->hora_inicio);
                return $inicio->betweenIncluded($desde, $hasta);
            });

        foreach ($turnos as $turno) {

            // Si ya venció, no mandar nada
            if ($turno->finDateTime()->isPast()) {
                continue;
            }

            $pago = $turno->pago;

            // Si ya pagó, no mandar
            if ($pago && $pago->estado === Pago::ESTADO_APROBADO) {
                continue;
            }

            // Evitar duplicado (si ya enviaste recordatorio)
            if ($pago && $pago->recordatorio_pago_enviado_at) {
                continue;
            }

            // Link firmado para pagar desde mail (sin login)
            $urlPago = URL::signedRoute('mp.pagar.mail', [
                'turno' => $turno->id,
                'alumno_id' => $turno->alumno_id,
            ]);

            Mail::to($turno->alumno->email)->send(
                new RecordatorioPagoTurno($turno, $urlPago)
            );

            // Si no existía pago, lo creo mínimo para guardar la marca de recordatorio
            if (! $pago) {
                $pago = Pago::create([
                    'turno_id' => $turno->id,
                    'monto' => $turno->precio_total,
                    'moneda' => config('services.mercadopago.currency', 'ARS'),
                    'estado' => Pago::ESTADO_PENDIENTE,
                    'provider' => 'mercadopago',
                    'external_reference' => "turno:{$turno->id}",
                ]);
            }

            $pago->update(['recordatorio_pago_enviado_at' => now()]);
        }
    }
}
