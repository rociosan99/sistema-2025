<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarSlotLiberadoJob;
use App\Mail\ProfesorClaseCancelada;
use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TurnoCancelarPanelController extends Controller
{
    public function __invoke(Request $request, Turno $turno, AuditLogger $audit)
    {
        if ((int) $turno->alumno_id !== (int) Auth::id()) {
            abort(403);
        }

        // Si ya pasó la hora -> vencido
        if ($turno->finDateTime()->isPast()) {
            $estadoAntes = (string) $turno->estado;

            $turno->update(['estado' => Turno::ESTADO_VENCIDO]);

            $audit->log('turno.vencido', $turno, [
                'turno_id' => $turno->id,
                'motivo' => 'cancel_intento_fuera_de_hora',
                'estado_anterior' => $estadoAntes,
                'estado_nuevo' => Turno::ESTADO_VENCIDO,
            ]);

            return back()->with('error', 'Este turno ya venció.');
        }

        // Estados permitidos para cancelar (incluye confirmado si querés permitir cancelar pagado)
        if (! in_array($turno->estado, [
            Turno::ESTADO_PENDIENTE,
            Turno::ESTADO_ACEPTADO,
            Turno::ESTADO_PENDIENTE_PAGO,
            Turno::ESTADO_CONFIRMADO, // ✅ permitir cancelar pagado
        ], true)) {
            return back()->with('error', 'Este turno no se puede cancelar.');
        }

        $profesorId  = (int) $turno->profesor_id;
        $fecha       = $turno->fecha->toDateString();
        $horaInicio  = (string) $turno->hora_inicio;
        $horaFin     = (string) $turno->hora_fin;
        $estadoAntes = (string) $turno->estado;

        DB::transaction(function () use ($turno) {
            $turno->update(['estado' => Turno::ESTADO_CANCELADO]);
        });

        // ✅ Si canceló una clase pagada, avisar por mail al profesor
        if ($estadoAntes === Turno::ESTADO_CONFIRMADO) {
            $profesor = $turno->profesor; // relación en Turno

            if ($profesor && $profesor->email) {
                Mail::to($profesor->email)->send(
                    new ProfesorClaseCancelada($turno, $fecha, substr($horaInicio, 0, 5), substr($horaFin, 0, 5))
                );
            }
        }

        // ✅ auditoría negocio
        $audit->log('turno.cancelado_alumno', $turno, [
            'turno_id' => $turno->id,
            'alumno_id' => (int) $turno->alumno_id,
            'profesor_id' => (int) $turno->profesor_id,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'estado_anterior' => $estadoAntes,
            'estado_nuevo' => Turno::ESTADO_CANCELADO,
        ]);

        // ✅ IMPORTANTÍSIMO: si el que canceló tenía una solicitud activa,
        // expirar ofertas de ESE alumno para ese slot (para que no vuelva a aparecer como reemplazo)
        OfertaSolicitud::query()
            ->where('profesor_id', $profesorId)
            ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
            ->where('hora_inicio', $horaInicio)
            ->where('hora_fin', $horaFin)
            ->whereHas('solicitud', function ($q) use ($turno, $fecha) {
                $q->where('alumno_id', (int) $turno->alumno_id)
                  ->whereDate('fecha', $fecha)
                  ->where('estado', SolicitudDisponibilidad::ESTADO_ACTIVA);
            })
            ->update(['estado' => OfertaSolicitud::ESTADO_EXPIRADA]);

        // ✅ disparar matching reactivo (si tu job acepta excluir alumno)
        dispatch(new ProcesarSlotLiberadoJob(
            $profesorId,
            $fecha,
            $horaInicio,
            $horaFin,
            (int) $turno->alumno_id // excluida
        ));

        $audit->log('matching.slot_liberado_disparado', $turno, [
            'turno_id' => $turno->id,
            'profesor_id' => $profesorId,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'job' => ProcesarSlotLiberadoJob::class,
        ]);

        return back()->with('success', 'Clase cancelada.');
    }
}
