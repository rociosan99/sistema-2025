<?php

namespace App\Jobs;

use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Models\Turno;
use App\Services\SolicitudMatchingService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerarOfertasInteligentesDesdeSolicitudesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SolicitudMatchingService $matcher): void
    {
        $maxSolicitudes = (int) config('matching.intelligent_max_solicitudes_per_run', 200);
        $maxOffersPorSolicitud = (int) config('matching.intelligent_max_offers_per_solicitud', 5);
        $ttlMin = (int) config('matching.intelligent_offer_ttl_minutes', 1440);

        $solicitudes = SolicitudDisponibilidad::query()
            ->where('estado', SolicitudDisponibilidad::ESTADO_ACTIVA)
            ->whereDate('fecha', '>=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->limit($maxSolicitudes)
            ->get();

        foreach ($solicitudes as $solicitud) {
            if ($this->solicitudYaPaso($solicitud)) {
                $solicitud->update([
                    'estado' => SolicitudDisponibilidad::ESTADO_EXPIRADA,
                ]);

                continue;
            }

            $slots = $this->generarSlotsDeUnaHora(
                (string) $solicitud->hora_inicio,
                (string) $solicitud->hora_fin
            );

            if (empty($slots)) {
                continue;
            }

            $creadasParaSolicitud = 0;
            $fecha = $solicitud->fecha->toDateString();

            foreach ($slots as [$slotInicio, $slotFin]) {
                if ($creadasParaSolicitud >= $maxOffersPorSolicitud) {
                    break;
                }

                // No generar ofertas para horarios ya empezados o vencidos.
                if ($this->slotYaNoEsOfertable($fecha, $slotInicio)) {
                    continue;
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
    }

    private function solicitudYaPaso(SolicitudDisponibilidad $solicitud): bool
    {
        $fecha = Carbon::parse($solicitud->fecha)->format('Y-m-d');
        $horaFin = $this->normalizarHora((string) $solicitud->hora_fin);

        return Carbon::parse($fecha . ' ' . $horaFin)->lte(now());
    }

    private function slotYaNoEsOfertable(string $fecha, string $slotInicio): bool
    {
        return Carbon::parse($fecha . ' ' . $this->normalizarHora($slotInicio))->lte(now());
    }

    private function generarSlotsDeUnaHora(string $horaInicio, string $horaFin): array
    {
        $horaInicio = $this->normalizarHora($horaInicio);
        $horaFin = $this->normalizarHora($horaFin);

        $inicio = Carbon::createFromFormat('H:i:s', $horaInicio);
        $fin = Carbon::createFromFormat('H:i:s', $horaFin);

        if ($inicio->gte($fin)) {
            return [];
        }

        $slots = [];
        $cursor = $inicio->copy();

        while ($cursor->lt($fin)) {
            $siguiente = $cursor->copy()->addHour();

            if ($siguiente->gt($fin)) {
                break;
            }

            $slots[] = [
                $cursor->format('H:i:s'),
                $siguiente->format('H:i:s'),
            ];

            $cursor = $siguiente;
        }

        return $slots;
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
            return $hora . ':00';
        }

        return $hora;
    }
}