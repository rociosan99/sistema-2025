<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Turno;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
    }

    public function crearLinkDePagoParaTurno(Turno $turno): Pago
    {
        $turno->loadMissing(['alumno', 'profesor', 'materia', 'tema']);

        $externalReference = "turno:{$turno->id}";

        $client = new PreferenceClient();

        $preference = $client->create([
            "items" => [[
                "id" => (string) $turno->id,
                "title" => "Clase - " . ($turno->materia?->materia_nombre ?? 'Materia'),
                "description" => "Turno {$turno->fecha_formateada} {$turno->horario} con {$turno->profesor?->name}",
                "currency_id" => config('services.mercadopago.currency', 'ARS'),
                "quantity" => 1,
                "unit_price" => (float) $turno->precio_total,
            ]],
            "payer" => [
                "name" => $turno->alumno?->name,
                "email" => $turno->alumno?->email,
            ],
            "external_reference" => $externalReference,

            // ✅ back_urls (plural). OJO: necesitan ser https público (ngrok / producción)
            "back_urls" => [
                "success" => route('mp.success', ['turno' => $turno->id]),
                "failure" => route('mp.failure', ['turno' => $turno->id]),
                "pending" => route('mp.pending', ['turno' => $turno->id]),
            ],

            // ✅ Webhook (también necesita ser https público)
            "notification_url" => route('mp.webhook'),

            // ✅ lo activás cuando ya estés con https público (ngrok)
            // "auto_return" => "approved",
        ]);

        return Pago::updateOrCreate(
            ['turno_id' => $turno->id],
            [
                'monto' => $turno->precio_total,
                'moneda' => config('services.mercadopago.currency', 'ARS'),
                'estado' => Pago::ESTADO_PENDIENTE,
                'provider' => 'mercadopago',
                'mp_preference_id' => $preference->id ?? null,
                'mp_init_point' => $preference->init_point ?? null,
                'external_reference' => $externalReference,
            ]
        );
    }
}
