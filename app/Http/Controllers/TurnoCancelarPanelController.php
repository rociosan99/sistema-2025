<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarSlotLiberadoJob;
use App\Models\Turno;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TurnoCancelarPanelController extends Controller
{
    public function __invoke(Request $request, Turno $turno, AuditLogger $audit)
    {
        if ((int) $turno->alumno_id !== (int) Auth::id()) {
            abort(403);
        }

        // No cancelar si ya es final
        if (in_array($turno->estado, [
            Turno::ESTADO_CONFIRMADO,
            Turno::ESTADO_RECHAZADO,
            Turno::ESTADO_VENCIDO,
            Turno::ESTADO_CANCELADO,
        ], true)) {
            return back()->with('error', 'Este turno no se puede cancelar.');
        }

        // Si ya pasó la hora, marcar vencido
        if ($turno->finDateTime()->isPast()) {
            $turno->update(['estado' => Turno::ESTADO_VENCIDO]);

            $audit->log('turno.vencido', $turno, [
                'turno_id' => $turno->id,
                'motivo' => 'cancel_intento_fuera_de_hora',
                'fecha' => $turno->fecha?->toDateString(),
                'hora_fin' => (string) $turno->hora_fin,
                'estado_anterior' => $turno->getOriginal('estado'),
                'estado_nuevo' => Turno::ESTADO_VENCIDO,
            ]);

            return back()->with('error', 'Este turno ya venció.');
        }

        // Estados permitidos para cancelar
        if (! in_array($turno->estado, [
            Turno::ESTADO_PENDIENTE,
            Turno::ESTADO_ACEPTADO,
            Turno::ESTADO_PENDIENTE_PAGO,
        ], true)) {
            return back()->with('error', 'Estado inválido para cancelar.');
        }

        // Guardamos datos ANTES (por si más adelante cambia algo)
        $profesorId = (int) $turno->profesor_id;
        $fecha      = $turno->fecha->toDateString();
        $horaInicio = (string) $turno->hora_inicio; // HH:MM:SS
        $horaFin    = (string) $turno->hora_fin;    // HH:MM:SS
        $estadoAntes = (string) $turno->estado;

        DB::transaction(function () use ($turno) {
            $turno->update(['estado' => Turno::ESTADO_CANCELADO]);
        });

        // ✅ AUDITORÍA DE NEGOCIO
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

        /**
         * ✅ REACTIVO:
         * Al liberarse este slot, generamos ofertas SOLO para este profe/fecha/horario.
         */
        dispatch(new ProcesarSlotLiberadoJob(
            $profesorId,
            $fecha,
            $horaInicio,
            $horaFin
        ));

        // ✅ AUDITORÍA: “se disparó matching reactivo”
        $audit->log('matching.slot_liberado_disparado', $turno, [
            'turno_id' => $turno->id,
            'profesor_id' => $profesorId,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'job' => ProcesarSlotLiberadoJob::class,
        ]);

        return back()->with('success', 'Turno cancelado.');
    }
}
