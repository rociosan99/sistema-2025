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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EnviarRecordatorioPago24hJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $mañana = Carbon::tomorrow()->toDateString();

        $turnos = Turno::query()
            ->with(['alumno', 'profesor', 'materia', 'pago'])
            ->whereDate('fecha', $mañana)
            ->where('estado', Turno::ESTADO_PENDIENTE_PAGO)
            ->get();

        foreach ($turnos as $turno) {
            // si no hay pago, lo tratamos como pendiente
            $pago = $turno->pago;

            // ya pagado => no recordatorio
            if ($pago && $pago->estado === Pago::ESTADO_APROBADO) {
                continue;
            }

            // evitar duplicado
            if ($pago && $pago->recordatorio_pago_enviado_at) {
                continue;
            }

            // link firmado para el mail
            $urlPago = URL::signedRoute('mp.pagar.mail', [
                'turno' => $turno->id,
                'alumno_id' => $turno->alumno_id,
            ]);

            Mail::to($turno->alumno->email)->send(
                new RecordatorioPagoTurno($turno, $urlPago)
            );

            // marcar recordatorio enviado (si no existe pago, lo creamos mínimo)
            if (! $pago) {
                $pago = Pago::create([
                    'turno_id' => $turno->id,
                    'monto' => $turno->precio_total,
                    'moneda' => 'ARS',
                    'estado' => Pago::ESTADO_PENDIENTE,
                    'provider' => 'mercadopago',
                    'external_reference' => "turno:{$turno->id}",
                ]);
            }

            $pago->update(['recordatorio_pago_enviado_at' => now()]);
        }
    }
}