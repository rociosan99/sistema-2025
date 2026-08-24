<?php

namespace App\Mail;

use App\Filament\Alumno\Pages\ResponderOfertaProfesor;
use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class ProfesorRespondioTurno extends Mailable
{
    use Queueable, SerializesModels;

    public Turno $turno;
    public string $urlPanelAlumno;

    public function __construct(Turno $turno)
    {
        $this->turno = $turno->loadMissing(['alumno', 'profesor', 'materia', 'tema']);

        if ($this->turno->estado === Turno::ESTADO_ACEPTADO) {
            $detalleUrl = ResponderOfertaProfesor::getUrl(
                ['record' => $this->turno->id],
                panel: 'alumno',
            );

            $destino = parse_url($detalleUrl, PHP_URL_PATH);

            if (! is_string($destino) || $destino === '') {
                throw new \RuntimeException('No se pudo generar el destino de la propuesta.');
            }

            $venceEn = now()->addDays(7);
            $inicioTurno = $this->turno->inicioDateTime();

            if ($inicioTurno->lt($venceEn)) {
                $venceEn = $inicioTurno;
            }

            $this->urlPanelAlumno = URL::temporarySignedRoute(
                'mail.access',
                $venceEn,
                [
                    'panel' => 'alumno',
                    'alumno' => (int) $this->turno->alumno_id,
                    'target' => base64_encode($destino),
                ],
            );
        } elseif ($this->turno->estado === Turno::ESTADO_PENDIENTE_PAGO) {
            $this->urlPanelAlumno = URL::signedRoute('mp.pagar.mail', [
                'turno' => $this->turno->id,
                'alumno_id' => $this->turno->alumno_id,
            ]);
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
