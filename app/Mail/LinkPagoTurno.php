<?php

namespace App\Mail;

use App\Models\Pago;
use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LinkPagoTurno extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Turno $turno,
        public Pago $pago
    ) {}

    public function build()
    {
        return $this
            ->subject('Pago de tu clase')
            ->markdown('emails.turno-link-pago', [
                'turno' => $this->turno,
                'pago'  => $this->pago,
            ]);
    }
}
