<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarReemplazoTurnoCanceladoJob;
use App\Models\SlotHold;
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

        if ($turno->finDateTime()->isPast()) {
            $estadoAntes = (string) $turno->estado;
            $turno->update(['estado' => Turno::ESTADO_VENCIDO]);

            $audit->log('turno.vencido', $turno, [
                'turno_id' => $turno->id,
                'motivo' => 'cancel_intento_fuera_de_hora',
                'estado_anterior' => $estadoAntes,
                'estado_nuevo' => Turno::ESTADO_VENCIDO,
            ]);

            return back()->with('error', 'La clase ya finalizó.');
        }

        if (! in_array($turno->estado, [
            Turno::ESTADO_PENDIENTE,
            Turno::ESTADO_ACEPTADO,
            Turno::ESTADO_PENDIENTE_PAGO,
            Turno::ESTADO_CONFIRMADO,
        ], true)) {
            return back()->with('error', 'Este turno no se puede cancelar.');
        }

        $profesorId  = (int) $turno->profesor_id;
        $fecha       = $turno->fecha->toDateString();
        $horaInicio  = (string) $turno->hora_inicio;
        $horaFin     = (string) $turno->hora_fin;
        $estadoAntes = (string) $turno->estado;
        $alumnoId    = (int) $turno->alumno_id;

        $horasRegla = (int) config('turnos.cancelacion_sin_cargo_horas', 24);
        $horasHastaInicio = now()->diffInHours($turno->inicioDateTime(), false);
        $tipoCancelacion = $horasHastaInicio >= $horasRegla ? 'sin_cargo' : 'con_cargo';

        $replacementWindowMin = (int) config('matching.replacement_window_minutes', 60);

        DB::transaction(function () use (
            $turno,
            $profesorId,
            $fecha,
            $horaInicio,
            $horaFin,
            $alumnoId,
            $replacementWindowMin,
            $tipoCancelacion
        ) {
            $turno->update([
                'estado' => Turno::ESTADO_CANCELADO,
                'cancelado_at' => now(),
                'cancelacion_tipo' => $tipoCancelacion,
            ]);

            // ✅ Solo con_cargo hacemos hold
            if ($tipoCancelacion === 'con_cargo') {
                SlotHold::create([
                    'profesor_id' => $profesorId,
                    'fecha'       => $fecha,
                    'hora_inicio' => $horaInicio,
                    'hora_fin'    => $horaFin,
                    'motivo'      => 'reemplazo',
                    'estado'      => SlotHold::ESTADO_ACTIVO,
                    'expires_at'  => now()->addMinutes($replacementWindowMin),
                    'meta'        => [
                        'turno_cancelado_id' => $turno->id,
                        'alumno_cancelador_id' => $alumnoId,
                        'cancelacion_tipo' => $tipoCancelacion,
                    ],
                ]);
            }
        });

        $audit->log('turno.cancelado_alumno', $turno, [
            'turno_id' => $turno->id,
            'alumno_id' => $alumnoId,
            'profesor_id' => $profesorId,
            'fecha' => $fecha,
            'hora_inicio' => $horaInicio,
            'hora_fin' => $horaFin,
            'estado_anterior' => $estadoAntes,
            'estado_nuevo' => Turno::ESTADO_CANCELADO,
            'cancelacion_tipo' => $tipoCancelacion,
        ]);

        // ✅ Solo con_cargo disparamos reemplazo
        if ($tipoCancelacion === 'con_cargo') {
            dispatch(new ProcesarReemplazoTurnoCanceladoJob(
                turnoCanceladoId: (int) $turno->id,
                excludedAlumnoId: (int) $alumnoId
            ));

            $audit->log('reemplazo.turno_cancelado_disparado', $turno, [
                'turno_id' => $turno->id,
                'job' => ProcesarReemplazoTurnoCanceladoJob::class,
                'cancelacion_tipo' => $tipoCancelacion,
            ]);

            return back()->with('success', 'Clase cancelada con cargo. Se buscará un reemplazo.');
        }

        return back()->with('success', 'Clase cancelada sin cargo. Si querés, podés reprogramar desde el botón Reprogramar.');
    }
}