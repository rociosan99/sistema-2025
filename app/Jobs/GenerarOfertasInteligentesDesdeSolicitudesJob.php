<?php

namespace App\Jobs;

use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Services\SolicitudMatchingService;
use App\Services\SolicitudDisponibilidadVencimientoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerarOfertasInteligentesDesdeSolicitudesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly ?int $solicitudId = null) {}

    public function handle(SolicitudMatchingService $matcher): void
    {
        $vencimientoService = app(SolicitudDisponibilidadVencimientoService::class);
        $vencimientoService->sincronizarActivas(solicitudId: $this->solicitudId);

        $maxSolicitudes = (int) config('matching.intelligent_max_solicitudes_per_run', 200);
        $maxOffersPorSolicitud = (int) config('matching.intelligent_max_offers_per_solicitud', 5);
        $ttlMin = (int) config('matching.intelligent_offer_ttl_minutes', 1440);

        $solicitudIds = SolicitudDisponibilidad::query()
            ->where('estado', SolicitudDisponibilidad::ESTADO_ACTIVA)
            ->when(
                $this->solicitudId !== null,
                fn ($query) => $query->whereKey($this->solicitudId),
            )
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->limit($maxSolicitudes)
            ->pluck('id');

        foreach ($solicitudIds as $solicitudId) {
            DB::transaction(function () use (
                $solicitudId,
                $matcher,
                $maxOffersPorSolicitud,
                $ttlMin,
                $vencimientoService,
            ): void {
                $solicitud = SolicitudDisponibilidad::query()
                    ->whereKey($solicitudId)
                    ->where('estado', SolicitudDisponibilidad::ESTADO_ACTIVA)
                    ->lockForUpdate()
                    ->first();

                if (! $solicitud) {
                    return;
                }

                $this->procesarSolicitud(
                    $solicitud,
                    $matcher,
                    $maxOffersPorSolicitud,
                    $ttlMin,
                    $vencimientoService,
                );
            }, 3);
        }
    }

    private function procesarSolicitud(
        SolicitudDisponibilidad $solicitud,
        SolicitudMatchingService $matcher,
        int $maxOffersPorSolicitud,
        int $ttlMin,
        SolicitudDisponibilidadVencimientoService $vencimientoService,
    ): void {
        $slots = $vencimientoService->slotsFuturosOfertables($solicitud);

        if (empty($slots)) {
            return;
        }

        $creadasParaSolicitud = 0;
        $fecha = $solicitud->fecha->toDateString();

        foreach ($slots as [$slotInicio, $slotFin]) {
            if ($creadasParaSolicitud >= $maxOffersPorSolicitud) {
                break;
            }

            // No generar si el alumno ya tiene un turno en ese horario.
            if ($this->alumnoTieneChoque(
                (int) $solicitud->alumno_id,
                $fecha,
                $slotInicio,
                $slotFin
            )) {
                continue;
            }

            // Este service ya valida:
            // - profesor dicta materia
            // - profesor tiene disponibilidad que cubre el slot
            // - profesor no tiene turno solapado
            $candidatos = $matcher->profesoresCompatibles($solicitud, $slotInicio, $slotFin);

            if ($candidatos->isEmpty()) {
                continue;
            }

            foreach ($candidatos as $candidato) {
                if ($creadasParaSolicitud >= $maxOffersPorSolicitud) {
                    break 2;
                }

                $profesorId = (int) $candidato['profesor_id'];

                if ($this->alumnoYaCanceloConProfesorEnSlot(
                    (int) $solicitud->alumno_id,
                    $profesorId,
                    $fecha,
                    $slotInicio,
                    $slotFin
                )) {
                    continue;
                }

                $ofertaExistente = OfertaSolicitud::query()
                    ->where('solicitud_id', $solicitud->id)
                    ->where('profesor_id', $profesorId)
                    ->where('hora_inicio', $slotInicio)
                    ->where('hora_fin', $slotFin)
                    ->first();

                if ($ofertaExistente) {
                    if (in_array($ofertaExistente->estado, [
                        OfertaSolicitud::ESTADO_ACEPTADA,
                        OfertaSolicitud::ESTADO_RECHAZADA,
                    ], true)) {
                        continue;
                    }

                    if (
                        $ofertaExistente->estado === OfertaSolicitud::ESTADO_PENDIENTE &&
                        $ofertaExistente->expires_at &&
                        $ofertaExistente->expires_at->gt(now())
                    ) {
                        continue;
                    }

                    $ofertaExistente->update([
                        'estado' => OfertaSolicitud::ESTADO_PENDIENTE,
                        'expires_at' => now()->addMinutes($ttlMin),
                    ]);

                    $creadasParaSolicitud++;

                    continue;
                }

                OfertaSolicitud::create([
                    'solicitud_id' => $solicitud->id,
                    'profesor_id' => $profesorId,
                    'hora_inicio' => $slotInicio,
                    'hora_fin' => $slotFin,
                    'estado' => OfertaSolicitud::ESTADO_PENDIENTE,
                    'expires_at' => now()->addMinutes($ttlMin),
                ]);

                $creadasParaSolicitud++;
            }
        }
    }

    private function alumnoTieneChoque(int $alumnoId, string $fecha, string $slotInicio, string $slotFin): bool
    {
        return Turno::query()
            ->where('alumno_id', $alumnoId)
            ->whereDate('fecha', $fecha)
            ->whereIn('estado', [
                Turno::ESTADO_PENDIENTE,
                Turno::ESTADO_ACEPTADO,
                Turno::ESTADO_PENDIENTE_PAGO,
                Turno::ESTADO_CONFIRMADO,
            ])
            ->where(function ($q) use ($slotInicio, $slotFin) {
                $q->where('hora_inicio', '<', $slotFin)
                    ->where('hora_fin', '>', $slotInicio);
            })
            ->exists();
    }

    private function alumnoYaCanceloConProfesorEnSlot(
        int $alumnoId,
        int $profesorId,
        string $fecha,
        string $slotInicio,
        string $slotFin
    ): bool {
        return Turno::query()
            ->where('alumno_id', $alumnoId)
            ->where('profesor_id', $profesorId)
            ->whereDate('fecha', $fecha)
            ->where('estado', Turno::ESTADO_CANCELADO)
            ->where(function ($q) use ($slotInicio, $slotFin) {
                $q->where('hora_inicio', '<', $slotFin)
                    ->where('hora_fin', '>', $slotInicio);
            })
            ->exists();
    }

    private function normalizarHora(string $hora): string
    {
        $hora = trim($hora);

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+(\d{2}:\d{2}:\d{2})$/', $hora, $m)) {
            return $m[1];
        }

        if (preg_match('/^\d{2}:\d{2}$/', $hora)) {
            return $hora.':00';
        }

        return $hora;
    }
}
