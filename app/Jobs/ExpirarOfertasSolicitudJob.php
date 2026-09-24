<?php

namespace App\Jobs;

use App\Models\OfertaSolicitud;
use App\Services\SolicitudDisponibilidadVencimientoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpirarOfertasSolicitudJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SolicitudDisponibilidadVencimientoService $vencimientoService): void
    {
        OfertaSolicitud::query()
            ->where('estado', OfertaSolicitud::ESTADO_PENDIENTE)
            ->where('expires_at', '<=', now())
            ->update([
                'estado' => OfertaSolicitud::ESTADO_EXPIRADA,
            ]);

        $vencimientoService->sincronizarActivas();
    }
}
