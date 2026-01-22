<?php

namespace App\Jobs;

use App\Mail\TurnoRecordatorio24h;
use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatorios24hJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Si fecha = null -> usa mañana (producción)
     * Si fecha tiene valor -> sirve para pruebas
     */
    public function __construct(public ?string $fecha = null) {}

    public function handle(): void
    {
        $fechaObjetivo = $this->fecha ?? now()->addDay()->toDateString();

        \App\Models\Turno::query()
            ->whereDate('fecha', $fechaObjetivo)
            ->where('estado', 'aceptado')
            ->whereNull('recordatorio_24h_enviado_at') // ✅ evita enviar repetido
            ->with(['alumno', 'profesor', 'materia', 'tema'])
            ->chunk(200, function ($turnos) {
                foreach ($turnos as $turno) {
                    \Illuminate\Support\Facades\Mail::to($turno->alumno->email)
                        ->send(new \App\Mail\TurnoRecordatorio24h($turno));

                    // ✅ marcar como enviado
                    $turno->forceFill([
                        'recordatorio_24h_enviado_at' => now(),
                    ])->save();
                }
            });
    }

}
