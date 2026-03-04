<?php

namespace App\Mail;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;


class ProfesorReemplazoConfirmado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Turno $turnoCancelado,
        public Turno $turnoNuevo,
    ) {
        $this->turnoCancelado->loadMissing(['alumno', 'materia', 'tema', 'profesor']);
        $this->turnoNuevo->loadMissing(['alumno', 'materia', 'tema', 'profesor']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu clase fue reasignada a otro alumno',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.profesor-reemplazo-confirmado',
            with: [
                'turnoCancelado' => $this->turnoCancelado,
                'turnoNuevo' => $this->turnoNuevo,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}