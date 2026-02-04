<?php

namespace App\Mail;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioPagoTurno extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Turno $turno,
        public string $urlPago
    ) {}

    public function build()
    {
        return $this
            ->subject('Recordatorio: falta abonar tu clase')
            ->view('mails.recordatorio-pago-turno', [
                'turno'   => $this->turno,
                'urlPago' => $this->urlPago,
            ]);
    }
}
