<?php

namespace App\Mail;

use App\Models\Turno;
use App\Services\AccesoMailAlumnoService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProfesorRespondioTurno extends Mailable
{
    use Queueable, SerializesModels;

    public Turno $turno;
    public string $urlPanelAlumno;

    public function __construct(Turno $turno)
    {
        $this->turno = $turno->loadMissing(['alumno', 'profesor', 'materia', 'tema']);

        if ($this->turno->estado === Turno::ESTADO_ACEPTADO) {
            $this->urlPanelAlumno = app(AccesoMailAlumnoService::class)->enlacePropuesta($this->turno);
        } elseif ($this->turno->estado === Turno::ESTADO_PENDIENTE_PAGO) {
            $this->urlPanelAlumno = app(AccesoMailAlumnoService::class)->enlacePago($this->turno);
        } else {
            $this->urlPanelAlumno = url('/alumno/turnos');
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Respuesta del profesor a tu solicitud',
        );
    }

    public function content(): Content
    {
        return new Content(
            // ✅ Blade en: resources/views/emails/profesor/profesor-respondio-turno.blade.php
            view: 'emails.profesor.profesor-respondio-turno',
            with: [
                'turno' => $this->turno,
                'urlPanelAlumno' => $this->urlPanelAlumno,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
