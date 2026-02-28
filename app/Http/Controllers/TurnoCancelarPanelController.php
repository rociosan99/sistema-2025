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

        // Si ya pasó la clase -> finalizada
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

        // Permitir cancelar incluso pagada
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

        // ✅ Regla 24 horas
        $horasRegla = (int) config('turnos.cancelacion_sin_cargo_horas', 24);
        $horasHastaInicio = now()->diffInHours($turno->inicioDateTime(), false);
        $tipoCancelacion = $horasHastaInicio >= $horasRegla ? 'sin_cargo' : 'con_cargo';

        // Hold / ventana de reemplazo
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

            // ✅ HOLD: bloquear que se publique en slots generales por X minutos
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
        });

        // Auditoría
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

        // ✅ Nuevo flujo B: buscar reemplazo SIEMPRE (tanto sin_cargo como con_cargo)
        dispatch(new ProcesarReemplazoTurnoCanceladoJob(
            turnoCanceladoId: (int) $turno->id,
            excludedAlumnoId: (int) $alumnoId
        ));

        $audit->log('reemplazo.turno_cancelado_disparado', $turno, [
            'turno_id' => $turno->id,
            'job' => ProcesarReemplazoTurnoCanceladoJob::class,
            'cancelacion_tipo' => $tipoCancelacion,
        ]);

        // Mensaje al alumno: si sin cargo, sugerimos reprogramar (solo UI)
        $msg = $tipoCancelacion === 'sin_cargo'
            ? 'Clase cancelada sin cargo. Podés reprogramar. Se buscará un reemplazo para el profesor.'
            : 'Clase cancelada. Se buscará un reemplazo (cancelación cercana al horario).';

        return back()->with('success', $msg);
    }
}