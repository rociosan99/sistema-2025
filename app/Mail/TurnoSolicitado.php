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

    // ✅ Datos extra
    public ?string $institucionNombre = null;
    public ?string $carreraNombre = null;

    // ✅ URL al panel del profesor
    public string $urlPanelProfesor;

    /**
     * Recibe el turno recién creado + institución/carrera (opcional)
     */
    public function __construct(Turno $turno, ?string $institucionNombre = null, ?string $carreraNombre = null)
    {
        // ✅ Asegurar relaciones para el blade
        $this->turno = $turno->loadMissing(['alumno', 'profesor', 'materia', 'tema']);

        $this->institucionNombre = $institucionNombre;
        $this->carreraNombre = $carreraNombre;

        // ✅ Donde el profe acepta/rechaza
        $this->urlPanelProfesor = url('/profesor/turnos');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de turno',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.turno-solicitado',
            with: [
                'turno' => $this->turno,
                'institucionNombre' => $this->institucionNombre,
                'carreraNombre' => $this->carreraNombre,
                'urlPanelProfesor' => $this->urlPanelProfesor,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
