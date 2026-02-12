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

class ProcesarSlotLiberadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $profesorId,
        public string $fecha,       // Y-m-d
        public string $horaInicio,  // H:i:s
        public string $horaFin,     // H:i:s
        public ?int $excludeAlumnoId = null, // ✅ NUEVO
    ) {}

    public function handle(SolicitudMatchingService $matcher): void
    {
        $ttlMin = (int) config('matching.offer_ttl_minutes', 30);

        // 1) Verificar que el profe realmente esté libre
        $hayChoque = Turno::query()
            ->where('profesor_id', $this->profesorId)
            ->whereDate('fecha', $this->fecha)
            ->whereIn('estado', [
                Turno::ESTADO_PENDIENTE,
                Turno::ESTADO_ACEPTADO,
                Turno::ESTADO_PENDIENTE_PAGO,
                Turno::ESTADO_CONFIRMADO,
            ])
            ->where(function ($q) {
                $q->where('hora_inicio', '<', $this->horaFin)
                  ->where('hora_fin', '>', $this->horaInicio);
            })
            ->exists();

        if ($hayChoque) {
            return;
        }

        // 2) Buscar solicitudes activas que encajen en este slot (intersección)
        $solicitudes = SolicitudDisponibilidad::query()
            ->where('estado', SolicitudDisponibilidad::ESTADO_ACTIVA)
            ->whereDate('fecha', $this->fecha)
            ->when($this->excludeAlumnoId, function ($q) {
                $q->where('alumno_id', '!=', $this->excludeAlumnoId); // ✅ EXCLUIR al que canceló
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                // solape con el slot liberado
                $q->where('hora_inicio', '<', $this->horaFin)
                  ->where('hora_fin', '>', $this->horaInicio);
            })
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        foreach ($solicitudes as $s) {

            // Si la solicitud ya pasó por hora_fin => expirar
            if ($this->solicitudYaPaso($s, $matcher)) {
                $s->update(['estado' => SolicitudDisponibilidad::ESTADO_EXPIRADA]);
                continue;
            }

            // 3) Slot usable = intersección solicitud vs slot liberado
            $slotInicio = max(
                $matcher->normalizarHora((string) $s->hora_inicio),
                $matcher->normalizarHora($this->horaInicio),
            );

            $slotFin = min(
                $matcher->normalizarHora((string) $s->hora_fin),
                $matcher->normalizarHora($this->horaFin),
            );

            if ($slotInicio >= $slotFin) {
                continue;
            }

            // 4) Ver si ESTE profesor es compatible para ese slot
            $candidatos = $matcher->profesoresCompatibles($s, $slotInicio, $slotFin);

            $esCandidato = $candidatos->firstWhere('profesor_id', $this->profesorId);

            if (! $esCandidato) {
                continue;
            }

            // 5) Crear/actualizar oferta por SLOT
            OfertaSolicitud::updateOrCreate(
                [
                    'solicitud_id' => $s->id,
                    'profesor_id'  => $this->profesorId,
                    'hora_inicio'  => $slotInicio,
                    'hora_fin'     => $slotFin,
                ],
                [
                    'estado'     => OfertaSolicitud::ESTADO_PENDIENTE,
                    'expires_at' => now()->addMinutes($ttlMin),
                ]
            );
        }
    }

    private function solicitudYaPaso(SolicitudDisponibilidad $s, SolicitudMatchingService $matcher): bool
    {
        $fecha = Carbon::parse($s->fecha)->format('Y-m-d');
        $horaFin = $matcher->normalizarHora((string) $s->hora_fin);
        $fin = Carbon::parse($fecha . ' ' . $horaFin);

        return $fin->isPast();
    }
}
