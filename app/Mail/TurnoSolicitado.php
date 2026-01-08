<?php

namespace App\Mail;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TurnoSolicitado extends Mailable
{
    use Queueable, SerializesModels;

    public Turno $turno;

    /**
     * Recibe el turno recién creado
     */
    public function __construct(Turno $turno)
    {
        $this->turno = $turno;
    }

    /**
     * Asunto del mail
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de turno',
        );
    }

    /**
     * Vista que se va a renderizar
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.turno-solicitado',
            with: [
                'turno' => $this->turno,
            ],
        );
    }

    /**
     * Adjuntos (no usamos por ahora)
     */
    public function attachments(): array
    {
        return [];
    }
}
