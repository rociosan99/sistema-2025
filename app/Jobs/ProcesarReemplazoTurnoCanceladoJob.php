<?php

namespace App\Jobs;

use App\Mail\AlumnoInvitacionReemplazo;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Models\TurnoReemplazo;
use App\Services\SolicitudMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ProcesarReemplazoTurnoCanceladoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $turnoCanceladoId,
        public ?int $excludedAlumnoId = null,
    ) {}

    public function handle(SolicitudMatchingService $matcher): void
    {
        $ttlMin     = (int) config('matching.replacement_invite_ttl_minutes', 30);
        $maxInvites = (int) config('matching.replacement_max_invites', 10);

        $turno = Turno::with(['profesor', 'materia', 'tema'])
            ->find($this->turnoCanceladoId);

        if (! $turno) {
            return;
        }

        // Solo si sigue cancelado y sin reemplazo
        if ((string) $turno->estado !== Turno::ESTADO_CANCELADO) {
            return;
        }

        if (! empty($turno->reemplazado_por_turno_id)) {
            return;
        }

        $fecha = $turno->fecha->toDateString();
        $horaInicio = $matcher->normalizarHora((string) $turno->hora_inicio);
        $horaFin    = $matcher->normalizarHora((string) $turno->hora_fin);

        // Buscar solicitudes activas (misma fecha + misma materia + solape)
        $solicitudes = SolicitudDisponibilidad::query()
            ->where('estado', SolicitudDisponibilidad::ESTADO_ACTIVA)
            ->whereDate('fecha', $fecha)
            ->where('materia_id', $turno->materia_id)
            ->when($this->excludedAlumnoId, fn ($q) => $q->where('alumno_id', '!=', $this->excludedAlumnoId))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where('hora_inicio', '<', $horaFin)
                  ->where('hora_fin', '>', $horaInicio);
            })
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $creadas = 0;

        foreach ($solicitudes as $s) {
            if ($creadas >= $maxInvites) {
                break;
            }

            // Intersección slot cancelado vs solicitud
            $slotInicio = max(
                $matcher->normalizarHora((string) $s->hora_inicio),
                $horaInicio
            );

            $slotFin = min(
                $matcher->normalizarHora((string) $s->hora_fin),
                $horaFin
            );

            if ($slotInicio >= $slotFin) {
                continue;
            }

            // Crear/actualizar invitación para ese alumno
            $inv = TurnoReemplazo::updateOrCreate(
                [
                    'turno_cancelado_id' => $turno->id,
                    'alumno_id'          => $s->alumno_id,
                ],
                [
                    'profesor_id' => $turno->profesor_id,
                    'materia_id'  => $turno->materia_id,
                    'tema_id'     => $turno->tema_id,
                    'fecha'       => $fecha,
                    'hora_inicio' => $slotInicio,
                    'hora_fin'    => $slotFin,
                    'estado'      => TurnoReemplazo::ESTADO_PENDIENTE,
                    'expires_at'  => now()->addMinutes($ttlMin),
                ]
            );

            // ✅ Notificar por mail al alumno solo una vez
            $inv->refresh();

            // Requiere columna notificado_at (recomendado)
            if (isset($inv->notificado_at) && $inv->notificado_at !== null) {
                $creadas++;
                continue;
            }

            $inv->loadMissing(['alumno', 'materia', 'tema']);

            if ($inv->alumno?->email) {
                $urlAceptar = URL::signedRoute('reemplazos.responder', [
                    'turnoReemplazo' => $inv->id,
                    'accion' => 'aceptar',
                ]);

                $urlRechazar = URL::signedRoute('reemplazos.responder', [
                    'turnoReemplazo' => $inv->id,
                    'accion' => 'rechazar',
                ]);

                Mail::to($inv->alumno->email)->send(
                    new AlumnoInvitacionReemplazo($inv, $urlAceptar, $urlRechazar)
                );

                // si existe notificado_at, marcarlo
                if (array_key_exists('notificado_at', $inv->getAttributes())) {
                    $inv->update(['notificado_at' => now()]);
                }
            }

            $creadas++;
        }

        // Programar notificación si no se consigue reemplazo (tu lógica actual)
        dispatch(new NotificarReemplazoNoConseguidoJob($turno->id))
            ->delay(now()->addMinutes($ttlMin + 2));
    }
}