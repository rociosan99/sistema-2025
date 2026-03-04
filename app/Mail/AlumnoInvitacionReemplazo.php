<?php

namespace App\Mail;

use App\Models\TurnoReemplazo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlumnoInvitacionReemplazo extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TurnoReemplazo $inv,
        public string $urlAceptar,
        public string $urlRechazar,
    ) {}

    public function build()
    {
        return $this->subject('Nueva clase disponible para tu solicitud')
            ->view('mails.alumno-invitacion-reemplazo');
    }
}