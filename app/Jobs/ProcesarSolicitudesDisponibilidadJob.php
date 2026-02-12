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

class ProcesarSolicitudesDisponibilidadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SolicitudMatchingService $matcher): void
    {
        $maxOffers = (int) config('matching.max_offers_per_batch', 3);
        $ttlMin    = (int) config('matching.offer_ttl_minutes', 30);

        $solicitudes = SolicitudDisponibilidad::query()
            ->where('estado', SolicitudDisponibilidad::ESTADO_ACTIVA)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at')
            ->limit(30)
            ->get();

        foreach ($solicitudes as $s) {

            // Si el rango real ya pasó (fecha + hora_fin) -> expirar
            if ($this->solicitudYaPaso($s)) {
                $s->update(['estado' => SolicitudDisponibilidad::ESTADO_EXPIRADA]);
                continue;
            }

            $slots = $this->generarSlotsDeUnaHora((string) $s->hora_inicio, (string) $s->hora_fin);

            if (empty($slots)) {
                continue;
            }

            $creadas = 0;

            foreach ($slots as [$slotInicio, $slotFin]) {

                $candidatos = $matcher->profesoresCompatibles($s, $slotInicio, $slotFin);

                if ($candidatos->isEmpty()) {
                    continue;
                }

                foreach ($candidatos as $c) {
                    if ($creadas >= $maxOffers) {
                        break 2;
                    }

                    $profesorId = (int) $c['profesor_id'];

                    // ✅ NUEVO: si el alumno YA canceló un turno con ESTE profe en ESTE slot,
                    // NO volver a ofrecerle ese mismo profe para ese horario.
                    $yaCanceloConEsteProfeEnEsteSlot = Turno::query()
                        ->where('alumno_id', (int) $s->alumno_id)
                        ->where('profesor_id', $profesorId)
                        ->whereDate('fecha', Carbon::parse($s->fecha)->toDateString())
                        ->where('estado', Turno::ESTADO_CANCELADO)
                        ->where(function ($q) use ($slotInicio, $slotFin) {
                            // solape exacto/por intersección (más robusto)
                            $q->where('hora_inicio', '<', $slotFin)
                              ->where('hora_fin', '>', $slotInicio);
                        })
                        ->exists();

                    if ($yaCanceloConEsteProfeEnEsteSlot) {
                        continue;
                    }

                    // Evitar duplicar ofertas vigentes para mismo profe + mismo slot
                    $yaExisteVigente = OfertaSolicitud::query()
                        ->where('solicitud_id', $s->id)
                        ->where('profesor_id', $profesorId)
                        ->where('hora_inicio', $slotInicio)
                        ->where('hora_fin', $slotFin)
                        ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
                        ->where('expires_at', '>', now())
                        ->exists();

                    if ($yaExisteVigente) {
                        continue;
                    }

                    OfertaSolicitud::updateOrCreate(
                        [
                            'solicitud_id' => $s->id,
                            'profesor_id'  => $profesorId,
                            'hora_inicio'  => $slotInicio,
                            'hora_fin'     => $slotFin,
                        ],
                        [
                            'estado'     => OfertaSolicitud::ESTADO_PENDIENTE,
                            'expires_at' => now()->addMinutes($ttlMin),
                        ]
                    );

                    $creadas++;
                }
            }
        }
    }

    private function solicitudYaPaso(SolicitudDisponibilidad $s): bool
    {
        $fecha = Carbon::parse($s->fecha)->format('Y-m-d');
        $horaFin = (string) $s->hora_fin; // HH:MM:SS

        return Carbon::parse($fecha . ' ' . $horaFin)->isPast();
    }

    private function generarSlotsDeUnaHora(string $horaInicio, string $horaFin): array
    {
        $tInicio = Carbon::createFromFormat('H:i:s', $horaInicio);
        $tFin    = Carbon::createFromFormat('H:i:s', $horaFin);

        if ($tInicio->gte($tFin)) {
            return [];
        }

        $slots = [];
        $cursor = $tInicio->copy();

        while ($cursor->lt($tFin)) {
            $next = $cursor->copy()->addHour();

            if ($next->gt($tFin)) {
                break;
            }

            $slots[] = [$cursor->format('H:i:s'), $next->format('H:i:s')];
            $cursor = $next;
        }

        return $slots;
    }
}
