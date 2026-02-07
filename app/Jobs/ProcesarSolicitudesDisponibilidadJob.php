<?php

namespace App\Jobs;

use App\Models\OfertaSolicitud;
use App\Models\SolicitudDisponibilidad;
use App\Services\SolicitudMatchingService;
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
        // Ajustá estos estados según tu modelo real
        $solicitudes = SolicitudDisponibilidad::query()
            ->whereIn('estado', ['activa', 'pendiente']) // dejalo como tengas en tu tabla
            ->orderBy('created_at')
            ->limit(100)
            ->get();

       $ttlMin = (int) (config('matching.offer_ttl_minutes', 30) ?: 30);


        foreach ($solicitudes as $solicitud) {

            $candidatos = $matcher->generarOfertasCandidatas($solicitud);

            foreach ($candidatos as $cand) {
                // ✅ crea una oferta por (solicitud, profesor, tramo)
                OfertaSolicitud::updateOrCreate(
                    [
                        'solicitud_id' => $solicitud->id,
                        'profesor_id'  => (int) $cand['profesor_id'],
                        'hora_inicio'  => $cand['hora_inicio'],
                        'hora_fin'     => $cand['hora_fin'],
                    ],
                    [
                        'estado'     => OfertaSolicitud::ESTADO_PENDIENTE,
                        'expires_at' => now()->addMinutes($ttlMin),
                    ]
                );
            }
        }
    }
}
