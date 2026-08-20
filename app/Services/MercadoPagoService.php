<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Turno;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        if (empty($turno->precio_total) || (float) $turno->precio_total <= 0) {
            $precioPorHora = (float) DB::table('profesor_materia')
                ->where('profesor_id', $turno->profesor_id)
                ->where('materia_id', $turno->materia_id)
                ->value('precio_por_hora');

            if ($precioPorHora > 0) {
                $inicio = Carbon::createFromFormat('H:i:s', substr((string) $turno->hora_inicio, 0, 8));
                $fin    = Carbon::createFromFormat('H:i:s', substr((string) $turno->hora_fin, 0, 8));
                $horas  = $inicio->diffInMinutes($fin) / 60;

                $turno->update([
                    'precio_por_hora' => $precioPorHora,
                    'precio_total'    => round($precioPorHora * $horas, 2),
                ]);

                $turno->refresh();
            }
        }

        $precioTotal = (float) $turno->precio_total;

        if ($precioTotal <= 0) {
            Log::error('MP: precio_total inválido', [
                'turno_id' => $turno->id,
                'precio_total' => $turno->precio_total,
            ]);

            throw new \RuntimeException(
                "No se puede generar link de pago: precio_total inválido para el turno {$turno->id} (valor: " . var_export($turno->precio_total, true) . ")"
            );
        }

        $precioTotal = round($precioTotal, 2);
        $pagoExistente = Pago::query()->where('turno_id', $turno->id)->first();
        $creditoReservado = (string) $turno->aplicacionesCredito()
            ->where('estado', \App\Models\CreditoAplicacion::ESTADO_RESERVADO)
            ->sum('importe');

        if ($this->aCentavos($creditoReservado) > 0) {
            if (
                ! $pagoExistente
                || $pagoExistente->estado !== Pago::ESTADO_PENDIENTE
                || ! preg_match('/^turno:'.$turno->id.':intento:[0-9a-f-]{36}$/i', (string) $pagoExistente->external_reference)
            ) {
                throw ValidationException::withMessages([
                    'credito' => 'La composición del intento de pago parcial no es válida.',
                ]);
            }

            $precio = round((float) $pagoExistente->monto_mercadopago, 2);
            $externalReference = (string) $pagoExistente->external_reference;

            if (
                $this->aCentavos($creditoReservado) + $this->aCentavos((string) $precio)
                !== $this->aCentavos((string) $precioTotal)
            ) {
                throw ValidationException::withMessages([
                    'credito' => 'La composición del intento no coincide con el precio total.',
                ]);
            }
        } else {
            $precio = $precioTotal;
            $externalReference = "turno:{$turno->id}:intento:".Str::uuid();
        }

        if ($precio <= 0) {
            throw ValidationException::withMessages([
                'pago' => 'El importe a cobrar por Mercado Pago debe ser mayor que cero.',
            ]);
        }

        $client = new PreferenceClient();

        // OJO: el success vuelve a tu controller, procesa el payment_id y recién después redirige a turnos.
        $successUrl = $this->buildAbsoluteUrl(route('mp.success', ['turno' => $turno->id], false));
        $failureUrl = $this->buildAbsoluteUrl(route('mp.failure', ['turno' => $turno->id], false));
        $pendingUrl = $this->buildAbsoluteUrl(route('mp.pending', ['turno' => $turno->id], false));
        $webhookUrl = $this->buildAbsoluteUrl(route('mp.webhook', [], false));

        Log::info('MP preference URLs generadas', [
            'turno_id' => $turno->id,
            'success' => $successUrl,
            'failure' => $failureUrl,
            'pending' => $pendingUrl,
            'webhook' => $webhookUrl,
            'app_url' => config('app.url'),
        ]);

        try {
            $preference = $client->create([
                'items' => [[
                    'id' => (string) $turno->id,
                    'title' => 'Clase - ' . ($turno->materia?->materia_nombre ?? 'Materia'),
                    'description' => "Turno {$turno->fecha_formateada} {$turno->horario} con {$turno->profesor?->name}",
                    'currency_id' => config('services.mercadopago.currency', 'ARS'),
                    'quantity' => 1,
                    'unit_price' => $precio,
                ]],
                'payer' => [
                    'name' => $turno->alumno?->name,
                    'email' => $turno->alumno?->email,
                ],
                'external_reference' => $externalReference,
                'back_urls' => [
                    'success' => $successUrl,
                    'failure' => $failureUrl,
                    'pending' => $pendingUrl,
                ],
                'auto_return' => 'approved',
                'notification_url' => $webhookUrl,
            ]);
        } catch (MPApiException $e) {
            $response = $e->getApiResponse();
            $status   = $response?->getStatusCode();
            $content  = $response?->getContent();

            Log::error('MercadoPago API error', [
                'turno_id' => $turno->id,
                'status' => $status,
                'content' => $content,
                'success_url' => $successUrl,
                'failure_url' => $failureUrl,
                'pending_url' => $pendingUrl,
                'webhook_url' => $webhookUrl,
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
                'success_url' => $successUrl,
                'failure_url' => $failureUrl,
                'pending_url' => $pendingUrl,
                'webhook_url' => $webhookUrl,
            ]);

            throw $e;
        }

        return Pago::updateOrCreate(
            ['turno_id' => $turno->id],
            [
                'monto' => $turno->precio_total,
                'monto_mercadopago' => number_format($precio, 2, '.', ''),
                'moneda' => config('services.mercadopago.currency', 'ARS'),
                'estado' => Pago::ESTADO_PENDIENTE,
                'provider' => 'mercadopago',
                'mp_preference_id' => $preference->id ?? null,
                'mp_init_point' => $preference->init_point ?? null,
                'mp_payment_id' => null,
                'mp_status' => null,
                'mp_status_detail' => null,
                'mp_payment_type' => null,
                'mp_payment_method' => null,
                'detalle_externo' => null,
                'external_reference' => $externalReference,
                'fecha_aprobado' => null,
            ]
        );
    }

    private function buildAbsoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '') {
            return $appUrl . '/' . ltrim($path, '/');
        }

        return url($path);
    }

    private function aCentavos(string $importe): int
    {
        $importe = number_format((float) $importe, 2, '.', '');
        [$entero, $decimales] = explode('.', $importe, 2);

        return ((int) $entero * 100) + (int) $decimales;
    }
}
