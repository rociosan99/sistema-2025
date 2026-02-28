<?php

namespace App\Mail;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfesorReemplazoNoConseguido extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Turno $turnoCancelado)
    {
        $this->turnoCancelado->loadMissing(['alumno', 'materia', 'tema', 'profesor']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Se canceló una clase y no se consiguió reemplazo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.profesor-reemplazo-no-conseguido',
            with: [
                'turnoCancelado' => $this->turnoCancelado,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}