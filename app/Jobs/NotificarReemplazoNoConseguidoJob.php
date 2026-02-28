<?php

namespace App\Jobs;

use App\Mail\ProfesorReemplazoNoConseguido;
use App\Models\Turno;
use App\Models\TurnoReemplazo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotificarReemplazoNoConseguidoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $turnoCanceladoId) {}

    public function handle(): void
    {
        $turno = Turno::with(['profesor', 'alumno', 'materia', 'tema'])
            ->find($this->turnoCanceladoId);

        if (! $turno) return;

        // Si ya se reemplazó, no hacemos nada
        if (! empty($turno->reemplazado_por_turno_id)) return;

        // Si todavía hay invitaciones pendientes vigentes, no avisamos
        $pendientesVigentes = TurnoReemplazo::query()
            ->where('turno_cancelado_id', $turno->id)
            ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($pendientesVigentes) return;

        // Marcar invitaciones pendientes como expiradas (limpieza)
        TurnoReemplazo::query()
            ->where('turno_cancelado_id', $turno->id)
            ->where('estado', TurnoReemplazo::ESTADO_PENDIENTE)
            ->update(['estado' => TurnoReemplazo::ESTADO_EXPIRADO]);

        $profesor = $turno->profesor;
        if ($profesor && $profesor->email) {
            Mail::to($profesor->email)->send(new ProfesorReemplazoNoConseguido($turno));
        }
    }
}