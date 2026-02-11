<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
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

        // ✅ Blindaje: si vino sin precio_total, intentamos calcularlo desde profesor_materia
        if (empty($turno->precio_total) || (float)$turno->precio_total <= 0) {
            $precioPorHora = (float) DB::table('profesor_materia')
                ->where('profesor_id', $turno->profesor_id)
                ->where('materia_id', $turno->materia_id)
                ->value('precio_por_hora');

            if ($precioPorHora > 0) {
                $inicio = Carbon::createFromFormat('H:i:s', substr((string)$turno->hora_inicio, 0, 8));
                $fin    = Carbon::createFromFormat('H:i:s', substr((string)$turno->hora_fin, 0, 8));
                $horas  = $inicio->diffInMinutes($fin) / 60;

                $turno->update([
                    'precio_por_hora' => $precioPorHora,
                    'precio_total'    => round($precioPorHora * $horas, 2),
                ]);

                $turno->refresh();
            }
        }

        $precio = (float) $turno->precio_total;

        if ($precio <= 0) {
            Log::error('MP: precio_total inválido', [
                'turno_id' => $turno->id,
                'precio_total' => $turno->precio_total,
            ]);

            throw new \RuntimeException(
                "No se puede generar link de pago: precio_total inválido para el turno {$turno->id} (valor: " . var_export($turno->precio_total, true) . ")"
            );
        }

        $precio = round($precio, 2);

        $externalReference = "turno:{$turno->id}";
        $client = new PreferenceClient();

        try {
            $preference = $client->create([
                "items" => [[
                    "id" => (string) $turno->id,
                    "title" => "Clase - " . ($turno->materia?->materia_nombre ?? 'Materia'),
                    "description" => "Turno {$turno->fecha_formateada} {$turno->horario} con {$turno->profesor?->name}",
                    "currency_id" => config('services.mercadopago.currency', 'ARS'),
                    "quantity" => 1,
                    "unit_price" => $precio,
                ]],
                "payer" => [
                    "name" => $turno->alumno?->name,
                    "email" => $turno->alumno?->email,
                ],
                "external_reference" => $externalReference,
                "back_urls" => [
                    "success" => route('mp.success', ['turno' => $turno->id]),
                    "failure" => route('mp.failure', ['turno' => $turno->id]),
                    "pending" => route('mp.pending', ['turno' => $turno->id]),
                ],
                "notification_url" => route('mp.webhook'),
            ]);

        } catch (MPApiException $e) {
            $response = $e->getApiResponse();
            $status   = $response?->getStatusCode();
            $content  = $response?->getContent();

            Log::error('MercadoPago API error', [
                'turno_id' => $turno->id,
                'status' => $status,
                'content' => $content,
            ]);

            throw new \RuntimeException(
                'MercadoPago API error: ' . json_encode([
                    'status' => $status,
                    'content' => $content,
                ], JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::error('MercadoPago UNKNOWN error', [
                'turno_id' => $turno->id,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

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
