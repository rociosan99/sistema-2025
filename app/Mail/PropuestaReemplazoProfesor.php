<?php

namespace App\Mail;

use App\Filament\Profesor\Pages\ResolverPropuestaReemplazo;
use App\Models\Turno;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PropuestaReemplazoProfesor extends Mailable
{
    use Queueable, SerializesModels;

    public Turno $turno;
    public string $urlPropuesta;

    public function __construct(Turno $turno)
    {
        $this->turno = $turno->loadMissing([
            'alumno',
            'materia',
            'tema',
            'profesorReemplazoPropuesto',
        ]);

        if (
            ! $this->turno->reemplazo_profesor_propuesto_id
            || ! $this->turno->reemplazo_expires_at
            || $this->turno->reemplazo_expires_at->lte(now())
        ) {
            throw new \RuntimeException('La propuesta de reemplazo no está vigente.');
        }

        $detalleUrl = ResolverPropuestaReemplazo::getUrl(
            ['record' => $this->turno->id],
            panel: 'profesor',
        );

        $destino = parse_url($detalleUrl, PHP_URL_PATH);

        if (! is_string($destino) || $destino === '') {
            throw new \RuntimeException('No se pudo generar el destino de la propuesta.');
        }

        $this->urlPropuesta = URL::temporarySignedRoute(
            'mail.access',
            $this->turno->reemplazo_expires_at,
            [
                'panel' => 'profesor',
                'profesor' => (int) $this->turno->reemplazo_profesor_propuesto_id,
                'target' => base64_encode($destino),
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva propuesta de reemplazo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.propuesta-reemplazo-profesor',
            with: [
                'turno' => $this->turno,
                'urlPropuesta' => $this->urlPropuesta,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
