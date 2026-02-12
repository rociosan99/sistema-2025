<?php

namespace App\Mail;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProfesorClaseCancelada extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Turno $turno,
        public string $fecha,
        public string $horaInicio,
        public string $horaFin,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Clase cancelada: revisá ofertas para reemplazo')
            ->markdown('emails.profesor.clase-cancelada');
    }
}
