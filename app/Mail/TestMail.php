<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Asunto y configuración del mail
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prueba Mailtrap - Laravel 12',
        );
    }

    /**
     * Vista que se va a renderizar
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.test',
        );
    }

    /**
     * Adjuntos (no usamos ahora)
     */
    public function attachments(): array
    {
        return [];
    }
}
