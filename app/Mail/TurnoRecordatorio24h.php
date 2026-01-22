<?php

namespace App\Mail;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TurnoRecordatorio24h extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Turno $turno) {}

    public function build()
    {
        $confirmUrl = URL::temporarySignedRoute(
        'turnos.confirmar-asistencia',
        now()->addHours(30),
        ['turno' => $this->turno->id, 'alumno_id' => $this->turno->alumno_id]
    );

        $cancelUrl = URL::temporarySignedRoute(
        'turnos.cancelar-asistencia',
        now()->addHours(30),
        ['turno' => $this->turno->id, 'alumno_id' => $this->turno->alumno_id]
    );


        return $this->subject('Recordatorio de tu clase: confirmá asistencia')
            ->view('emails.turno-recordatorio-24h', [
                'turno' => $this->turno,
                'confirmUrl' => $confirmUrl,
                'cancelUrl' => $cancelUrl,
            ]);
    }
}
