<?php

namespace App\Mail;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfesorTurnoReprogramado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Turno $turnoOriginal,
        public Turno $turnoNuevo,
    ) {
        $this->turnoOriginal->loadMissing(['alumno', 'profesor', 'materia', 'tema']);
        $this->turnoNuevo->loadMissing(['alumno', 'profesor', 'materia', 'tema']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Una clase fue reprogramada',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.profesor-turno-reprogramado',
            with: [
                'turnoOriginal' => $this->turnoOriginal,
                'turnoNuevo' => $this->turnoNuevo,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}