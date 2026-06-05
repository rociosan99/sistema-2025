<?php

namespace App\Mail;

use App\Filament\Resources\SolicitudesUbicacion\SolicitudUbicacionResource;
use App\Models\SolicitudUbicacion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SolicitudUbicacionCreada extends Mailable
{
    use Queueable, SerializesModels;

    public SolicitudUbicacion $solicitud;
    public string $urlPanelSolicitudes;

    public function __construct(SolicitudUbicacion $solicitud)
    {
        $this->solicitud = $solicitud->loadMissing(['solicitante', 'pais', 'provincia']);
        $this->urlPanelSolicitudes = SolicitudUbicacionResource::getUrl('index', panel: 'admin');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva solicitud de ubicacion pendiente',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.solicitud-ubicacion-creada',
            with: [
                'solicitud' => $this->solicitud,
                'urlPanelSolicitudes' => $this->urlPanelSolicitudes,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
