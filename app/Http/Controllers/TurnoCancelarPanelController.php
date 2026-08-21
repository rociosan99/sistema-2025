<?php

namespace App\Http\Controllers;

use App\Filament\Alumno\Pages\SuspensionCompletada;
use App\Jobs\ProcesarReemplazoTurnoCanceladoJob;
use App\Models\SlotHold;
use App\Models\Turno;
use App\Services\AuditLogger;
use App\Services\CreditoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TurnoCancelarPanelController extends Controller
{
    public function __invoke(
        Request $request,
        Turno $turno,
        AuditLogger $audit,
        CreditoService $creditoService,
    ) {
        $request->validate([
            'acepta_terminos' => ['accepted'],
            'politica_version' => ['required', 'string', 'size:64'],
        ]);

        $alumnoAutenticadoId = (int) Auth::id();

        if ((int) $turno->alumno_id !== $alumnoAutenticadoId) {
            abort(403);
        }

        try {
            $politica = $creditoService->obtenerPoliticaVigente();
        } catch (ValidationException $exception) {
            return back()->with(
                'error',
                'La política de suspensión no está disponible o es inválida.',
            );
        }

        if (! hash_equals(
            $creditoService->huellaPolitica($politica),
            (string) $request->input('politica_version'),
        )) {
            return back()->with(
                'error',
                'La política de suspensión cambió. Recargá la página y revisá nuevamente los términos.',
            );
        }

        $replacementWindowMin = (int) config('matching.replacement_window_minutes', 60);

        try {
            $resultado = DB::transaction(function () use (
                $turno,
                $alumnoAutenticadoId,
                $politica,
                $replacementWindowMin,
                $creditoService,
            ) {
                $turnoBloqueado = Turno::query()
                    ->lockForUpdate()
                    ->findOrFail($turno->id);

                if ((int) $turnoBloqueado->alumno_id !== $alumnoAutenticadoId) {
                    abort(403);
                }

                if ($turnoBloqueado->finDateTime()->isPast()) {
                    $estadoAntes = (string) $turnoBloqueado->estado;
                    $turnoBloqueado->update(['estado' => Turno::ESTADO_VENCIDO]);

                    return [
                        'vencido' => true,
                        'turno_id' => $turnoBloqueado->id,
                        'estado_anterior' => $estadoAntes,
                    ];
                }

                if (! in_array($turnoBloqueado->estado, [
                    Turno::ESTADO_PENDIENTE,
                    Turno::ESTADO_ACEPTADO,
                    Turno::ESTADO_PENDIENTE_PAGO,
                    Turno::ESTADO_CONFIRMADO,
                ], true)) {
                    throw ValidationException::withMessages([
                        'turno' => 'Este turno no se puede suspender.',
                    ]);
                }

                $profesorId = (int) $turnoBloqueado->profesor_id;
                $fecha = $turnoBloqueado->fecha->toDateString();
                $horaInicio = (string) $turnoBloqueado->hora_inicio;
                $horaFin = (string) $turnoBloqueado->hora_fin;
                $estadoAntes = (string) $turnoBloqueado->estado;
                $alumnoId = (int) $turnoBloqueado->alumno_id;
                $canceladoAt = now();

                $limite = now()->copy()->addHours(
                    $politica->horas_cancelacion_sin_penalizacion,
                );
                $esAnticipada = $turnoBloqueado->inicioDateTime()->gte($limite);
                $tipoCancelacion = $esAnticipada ? 'sin_cargo' : 'con_cargo';

                $turnoBloqueado->update([
                    'estado' => Turno::ESTADO_CANCELADO,
                    'cancelado_at' => $canceladoAt,
                    'cancelacion_tipo' => $tipoCancelacion,
                ]);

                if ($tipoCancelacion === 'con_cargo') {
                    SlotHold::create([
                        'profesor_id' => $profesorId,
                        'fecha' => $fecha,
                        'hora_inicio' => $horaInicio,
                        'hora_fin' => $horaFin,
                        'motivo' => 'reemplazo',
                        'estado' => SlotHold::ESTADO_ACTIVO,
                        'expires_at' => now()->addMinutes($replacementWindowMin),
                        'meta' => [
                            'turno_cancelado_id' => $turnoBloqueado->id,
                            'alumno_cancelador_id' => $alumnoId,
                            'cancelacion_tipo' => $tipoCancelacion,
                        ],
                    ]);

                    DB::afterCommit(function () use ($turnoBloqueado, $alumnoId) {
                        ProcesarReemplazoTurnoCanceladoJob::dispatch(
                            turnoCanceladoId: (int) $turnoBloqueado->id,
                            excludedAlumnoId: $alumnoId,
                        );
                    });
                }

                $creditoService->registrarCancelacion(
                    $turnoBloqueado,
                    $politica,
                    $esAnticipada,
                );

                return [
                    'vencido' => false,
                    'turno_id' => $turnoBloqueado->id,
                    'alumno_id' => $alumnoId,
                    'profesor_id' => $profesorId,
                    'fecha' => $fecha,
                    'hora_inicio' => $horaInicio,
                    'hora_fin' => $horaFin,
                    'estado_anterior' => $estadoAntes,
                    'tipo_cancelacion' => $tipoCancelacion,
                ];
            });
        } catch (ValidationException $exception) {
            return back()->with(
                'error',
                $exception->validator->errors()->first() ?: 'No se pudo suspender la clase.',
            );
        }

        $turno->refresh();

        if ($resultado['vencido']) {
            $audit->log('turno.vencido', $turno, [
                'turno_id' => $resultado['turno_id'],
                'motivo' => 'cancel_intento_fuera_de_hora',
                'estado_anterior' => $resultado['estado_anterior'],
                'estado_nuevo' => Turno::ESTADO_VENCIDO,
            ]);

            return back()->with('error', 'La clase ya finalizó.');
        }

        $audit->log('turno.cancelado_alumno', $turno, [
            'turno_id' => $resultado['turno_id'],
            'alumno_id' => $resultado['alumno_id'],
            'profesor_id' => $resultado['profesor_id'],
            'fecha' => $resultado['fecha'],
            'hora_inicio' => $resultado['hora_inicio'],
            'hora_fin' => $resultado['hora_fin'],
            'estado_anterior' => $resultado['estado_anterior'],
            'estado_nuevo' => Turno::ESTADO_CANCELADO,
            'cancelacion_tipo' => $resultado['tipo_cancelacion'],
        ]);

        if ($resultado['tipo_cancelacion'] === 'con_cargo') {
            $audit->log('reemplazo.turno_cancelado_disparado', $turno, [
                'turno_id' => $resultado['turno_id'],
                'job' => ProcesarReemplazoTurnoCanceladoJob::class,
                'cancelacion_tipo' => $resultado['tipo_cancelacion'],
            ]);

            return redirect()->to(SuspensionCompletada::getUrl(
                ['record' => $turno->id],
                panel: 'alumno',
            ));
        }

        return redirect()->to(SuspensionCompletada::getUrl(
            ['record' => $turno->id],
            panel: 'alumno',
        ));
    }
}
