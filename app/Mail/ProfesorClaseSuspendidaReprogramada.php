<?php

namespace App\Mail;

use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfesorClaseSuspendidaReprogramada extends Mailable
{
    use Queueable, SerializesModels;

    public Turno $turno;

    public function __construct(Turno $turno)
    {
        $this->turno = $turno->loadMissing(['alumno', 'profesor', 'materia', 'tema']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Se reprogramó una clase suspendida',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.profesor-clase-suspendida-reprogramada',
            with: [
                'turno' => $this->turno,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
