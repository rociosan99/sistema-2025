<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Turno;
use App\Services\MercadoPagoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoController extends Controller
{
    public function pagar(Turno $turno, MercadoPagoService $mp)
    {
        // Solo se paga si está pendiente de pago
        if ($turno->estado !== 'pendiente_pago') {
            abort(403, 'El turno no está pendiente de pago.');
        }

        $pago = $turno->pago;

        if (! $pago || ! $pago->mp_init_point) {
            $pago = $mp->crearLinkDePagoParaTurno($turno);
        }

        return redirect()->away($pago->mp_init_point);
    }

    /**
     * Back URL: el usuario vuelve desde Mercado Pago.
     * Acá actualizamos el estado consultando a la API con payment_id.
     */
    public function success(Request $request, Turno $turno)
    {
        $paymentId = $request->query('payment_id');

        if (! $paymentId) {
            return view('turnos.confirmacion-resultado', [
                'titulo' => 'Volviste sin payment_id',
                'mensaje' => 'No pudimos verificar el pago. Volvé a intentar desde "Pagar".',
            ]);
        }

        $resultado = $this->procesarPagoDesdeMercadoPago((string) $paymentId, $turno);

        return view('turnos.confirmacion-resultado', [
            'titulo' => $resultado['titulo'],
            'mensaje' => $resultado['mensaje'],
        ]);
    }

    public function failure(Turno $turno)
    {
        // Si falla, el turno sigue pendiente_pago
        if ($turno->estado === 'pendiente_pago') {
            // no hacemos nada
        }

        return view('turnos.confirmacion-resultado', [
            'titulo' => 'Pago fallido o cancelado',
            'mensaje' => 'El pago fue cancelado o rechazado. Podés intentar de nuevo desde tu panel.',
        ]);
    }

    public function pending(Turno $turno)
    {
        // Si queda pending, el turno sigue pendiente_pago
        return view('turnos.confirmacion-resultado', [
            'titulo' => 'Pago pendiente',
            'mensaje' => 'El pago quedó pendiente. En cuanto se apruebe, se actualizará automáticamente.',
        ]);
    }

    /**
     * Webhook: Mercado Pago llama a tu sistema desde afuera.
     * Requiere URL pública (ngrok) y CSRF excluido (ya lo hiciste).
     */
    public function webhook(Request $request)
    {
        // MP suele enviar:
        // type=payment, data.id=123...
        $type = $request->input('type') ?? $request->input('topic');
        $paymentId = $request->input('data.id') ?? $request->input('id');

        if ($type !== 'payment' || ! $paymentId) {
            return response()->json(['ok' => true, 'ignored' => true]);
        }

        // Intentamos localizar el turno a partir del pago (si ya existe)
        $pago = Pago::where('provider', 'mercadopago')
            ->where('mp_payment_id', (string) $paymentId)
            ->first();

        // Si no existe todavía el pago con payment_id, igual podemos consultar a MP
        // y resolver por external_reference => "turno:ID"
        $turno = null;

        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

            $paymentClient = new PaymentClient();
            $payment = $paymentClient->get((int) $paymentId);

            $externalRef = (string) ($payment->external_reference ?? '');
            $turnoId = null;

            if (preg_match('/turno:(\d+)/', $externalRef, $m)) {
                $turnoId = (int) $m[1];
            }

            if ($turnoId) {
                $turno = Turno::find($turnoId);
            }

            if (! $turno) {
                return response()->json(['ok' => true, 'turno_not_found' => true]);
            }

            $this->procesarPagoDesdeObjetoMP($payment, $turno);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('Webhook MP error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['ok' => false], 500);
        }
    }

    /* ==========================================================
     * Helpers internos
     * ========================================================== */

    private function procesarPagoDesdeMercadoPago(string $paymentId, Turno $turno): array
    {
        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

            $paymentClient = new PaymentClient();
            $payment = $paymentClient->get((int) $paymentId);

            return $this->procesarPagoDesdeObjetoMP($payment, $turno);
        } catch (MPApiException $e) {
            Log::error('MPApiException al consultar pago', [
                'payment_id' => $paymentId,
                'turno_id' => $turno->id,
                'status' => $e->getApiResponse()?->getStatusCode(),
                'content' => $e->getApiResponse()?->getContent(),
            ]);

            return [
                'titulo' => 'No pudimos verificar el pago',
                'mensaje' => 'Ocurrió un error consultando Mercado Pago. Volvé a intentar en unos minutos.',
            ];
        } catch (\Throwable $e) {
            Log::error('Error general al procesar pago', [
                'payment_id' => $paymentId,
                'turno_id' => $turno->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'titulo' => 'Error',
                'mensaje' => 'Ocurrió un error inesperado. Revisá logs.',
            ];
        }
    }

    private function procesarPagoDesdeObjetoMP(object $payment, Turno $turno): array
    {
        $paymentId = (string) ($payment->id ?? '');
        $status = (string) ($payment->status ?? '');
        $statusDetail = (string) ($payment->status_detail ?? '');
        $externalRef = (string) ($payment->external_reference ?? '');

        // Validación: el pago debe corresponder al turno
        if ($externalRef !== "turno:{$turno->id}") {
            Log::warning('Pago con external_reference no coincide', [
                'turno_id' => $turno->id,
                'external_reference' => $externalRef,
                'payment_id' => $paymentId,
            ]);

            return [
                'titulo' => 'Pago inválido',
                'mensaje' => 'El pago no corresponde a este turno.',
            ];
        }

        // Crear/actualizar Pago en BD
        $pago = Pago::updateOrCreate(
            ['turno_id' => $turno->id],
            [
                'monto' => $turno->precio_total,
                'moneda' => config('services.mercadopago.currency', 'ARS'),
                'provider' => 'mercadopago',
                'mp_payment_id' => $paymentId,
                'mp_status' => $status,
                'mp_status_detail' => $statusDetail,
                'mp_payment_type' => (string) ($payment->payment_type_id ?? ''),
                'mp_payment_method' => (string) ($payment->payment_method_id ?? ''),
                'detalle_externo' => (array) $payment,
                'external_reference' => $externalRef,
                'fecha_aprobado' => $status === 'approved' ? Carbon::now() : null,
                'estado' => match ($status) {
                    'approved' => 'aprobado',
                    'rejected' => 'rechazado',
                    default => 'pendiente',
                },
            ]
        );

        // ✅ Actualizar estado del turno
        if ($status === 'approved') {
            $turno->update(['estado' => 'confirmado']); // pago OK
            return [
                'titulo' => 'Pago aprobado',
                'mensaje' => '¡Listo! Se aprobó el pago y el turno quedó Confirmado (pago OK).',
            ];
        }

        // Si está pending o rejected, dejamos el turno en pendiente_pago
        if ($turno->estado !== 'pendiente_pago') {
            $turno->update(['estado' => 'pendiente_pago']);
        }

        return [
            'titulo' => $status === 'rejected' ? 'Pago rechazado' : 'Pago pendiente',
            'mensaje' => $status === 'rejected'
                ? 'El pago fue rechazado. Podés intentar nuevamente.'
                : 'El pago quedó pendiente. Cuando se apruebe, se actualizará automáticamente.',
        ];
    }
}
