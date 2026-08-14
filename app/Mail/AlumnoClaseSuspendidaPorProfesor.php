<?php

namespace App\Mail;

use App\Filament\Alumno\Pages\ResolverSuspension;
use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlumnoClaseSuspendidaPorProfesor extends Mailable
{
    use Queueable, SerializesModels;

    public Turno $turno;
    public string $urlReprogramar;

    public function __construct(Turno $turno)
    {
        $this->turno = $turno->loadMissing(['alumno', 'profesor', 'materia', 'tema']);
        $this->urlReprogramar = ResolverSuspension::getUrl(
            ['record' => $this->turno->id],
            panel: 'alumno',
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu clase fue suspendida por el profesor',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alumno-clase-suspendida-por-profesor',
            with: [
                'turno' => $this->turno,
                'urlReprogramar' => $this->urlReprogramar,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
