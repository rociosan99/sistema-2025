<?php

namespace App\Mail;

use App\Filament\Alumno\Pages\VerTurnoAlumno;
use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlumnoReemplazoProfesorConfirmado extends Mailable
{
    use Queueable, SerializesModels;

    public string $urlTurnos;

    public function __construct(public Turno $turno)
    {
        $this->turno->loadMissing(['alumno', 'profesor', 'materia', 'tema']);
        $this->urlTurnos = VerTurnoAlumno::getUrl(
            ['record' => $this->turno->getKey()],
            panel: 'alumno',
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu profesor reemplazante confirmó la clase',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alumno-reemplazo-profesor-confirmado',
            with: [
                'turno' => $this->turno,
                'urlTurnos' => $this->urlTurnos,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
