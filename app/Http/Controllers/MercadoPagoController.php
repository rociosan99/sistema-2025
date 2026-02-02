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
    /**
     * Pago desde el panel (botón "Pagar")
     */
    public function pagar(Turno $turno, MercadoPagoService $mp)
    {
        // ✅ DEBUG (dejalo 1 vez y después BORRALO)
        //dd(substr((string) config('services.mercadopago.access_token'), 0, 8)); // TEST- o APP_USR-

        // Si ya está pagado
        if ($turno->estado === Turno::ESTADO_CONFIRMADO) {
            return view('turnos.confirmacion-resultado', [
                'titulo'  => 'Ya está pagado',
                'mensaje' => 'Este turno ya figura como Clase pagada.',
            ]);
        }

        // Solo se paga si está pendiente de pago
        if ($turno->estado !== Turno::ESTADO_PENDIENTE_PAGO) {
            abort(403, 'El turno no está pendiente de pago.');
        }

        $pago = $turno->pago;

        if (! $pago || ! $pago->mp_init_point) {
            $pago = $mp->crearLinkDePagoParaTurno($turno);
        }

        return redirect()->away($pago->mp_init_point);
    }

    /**
     * ✅ Pago desde MAIL (link firmado + alumno_id).
     * Funciona aunque el alumno NO esté logueado.
     *
     * Requiere route con middleware('signed')
     */
    public function pagarDesdeMail(Request $request, Turno $turno, MercadoPagoService $mp)
    {
        $alumnoId = (int) $request->query('alumno_id');

        if (! $alumnoId) {
            abort(403, 'Link inválido (falta alumno_id).');
        }

        if ((int) $turno->alumno_id !== $alumnoId) {
            abort(403, 'No autorizado.');
        }

        // Ya pagado
        if ($turno->estado === Turno::ESTADO_CONFIRMADO) {
            return view('turnos.confirmacion-resultado', [
                'titulo'  => 'Ya está pagado',
                'mensaje' => 'Este turno ya figura como Clase pagada.',
            ]);
        }

        if ($turno->estado !== Turno::ESTADO_PENDIENTE_PAGO) {
            return view('turnos.confirmacion-resultado', [
                'titulo'  => 'No disponible',
                'mensaje' => 'Este turno no está pendiente de pago.',
            ]);
        }

        $pago = $turno->pago;

        if (! $pago || ! $pago->mp_init_point) {
            $pago = $mp->crearLinkDePagoParaTurno($turno);
        }

        return redirect()->away($pago->mp_init_point);
    }

    /**
     * Back URL: vuelve el usuario desde Mercado Pago.
     */
    public function success(Request $request, Turno $turno)
    {
        $paymentId = $request->query('payment_id');

        if (! $paymentId) {
            return view('turnos.confirmacion-resultado', [
                'titulo'  => 'Volviste al sistema',
                'mensaje' => 'Si el pago se aprobó, el turno se actualizará automáticamente (webhook). Si no cambia, intentá de nuevo desde “Pagar”.',
            ]);
        }

        $resultado = $this->procesarPagoDesdeMercadoPago((string) $paymentId, $turno);

        return view('turnos.confirmacion-resultado', [
            'titulo'  => $resultado['titulo'],
            'mensaje' => $resultado['mensaje'],
        ]);
    }

    public function failure(Turno $turno)
    {
        return view('turnos.confirmacion-resultado', [
            'titulo'  => 'Pago fallido o cancelado',
            'mensaje' => 'El pago fue cancelado o rechazado. Podés intentar de nuevo desde tu panel.',
        ]);
    }

    public function pending(Turno $turno)
    {
        return view('turnos.confirmacion-resultado', [
            'titulo'  => 'Pago pendiente',
            'mensaje' => 'El pago quedó pendiente. Cuando se apruebe, se actualizará automáticamente.',
        ]);
    }

    /**
     * Webhook Mercado Pago (server-to-server)
     * (CSRF excluido en bootstrap/app.php)
     */
    public function webhook(Request $request)
    {
        Log::info('Webhook MP recibido', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        $paymentId =
            $request->input('data.id') ??
            ($request->input('data')['id'] ?? null) ??
            $request->input('id');

        if (! $paymentId && $request->filled('resource')) {
            if (preg_match('~/payments/(\d+)~', (string) $request->input('resource'), $m)) {
                $paymentId = $m[1];
            }
        }

        if (! $paymentId) {
            return response()->json(['ok' => true, 'ignored' => true, 'reason' => 'no_payment_id']);
        }

        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

            $paymentClient = new PaymentClient();
            $payment = $paymentClient->get((int) $paymentId);

            $externalRef = (string) ($payment->external_reference ?? '');
            $turnoId = null;

            if (preg_match('/turno:(\d+)/', $externalRef, $m)) {
                $turnoId = (int) $m[1];
            }

            if (! $turnoId) {
                Log::warning('Webhook MP sin external_reference de turno', [
                    'payment_id' => $paymentId,
                    'external_reference' => $externalRef,
                ]);

                return response()->json(['ok' => true, 'ignored' => true, 'reason' => 'no_turno_id']);
            }

            $turno = Turno::find($turnoId);

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
                'turno_id'   => $turno->id,
                'status'     => $e->getApiResponse()?->getStatusCode(),
                'content'    => $e->getApiResponse()?->getContent(),
            ]);

            return [
                'titulo'  => 'No pudimos verificar el pago',
                'mensaje' => 'Ocurrió un error consultando Mercado Pago. Volvé a intentar en unos minutos.',
            ];
        } catch (\Throwable $e) {
            Log::error('Error general al procesar pago', [
                'payment_id' => $paymentId,
                'turno_id'   => $turno->id,
                'error'      => $e->getMessage(),
            ]);

            return [
                'titulo'  => 'Error',
                'mensaje' => 'Ocurrió un error inesperado. Revisá logs.',
            ];
        }
    }

    private function procesarPagoDesdeObjetoMP(object $payment, Turno $turno): array
    {
        $paymentId    = (string) ($payment->id ?? '');
        $status       = (string) ($payment->status ?? '');
        $statusDetail = (string) ($payment->status_detail ?? '');
        $externalRef  = (string) ($payment->external_reference ?? '');

        if ($externalRef !== "turno:{$turno->id}") {
            Log::warning('Pago external_reference no coincide', [
                'turno_id'           => $turno->id,
                'external_reference' => $externalRef,
                'payment_id'         => $paymentId,
            ]);

            return [
                'titulo'  => 'Pago inválido',
                'mensaje' => 'El pago no corresponde a este turno.',
            ];
        }

        $detalle = json_decode(json_encode($payment), true);

        Pago::updateOrCreate(
            ['turno_id' => $turno->id],
            [
                'monto'              => $turno->precio_total,
                'moneda'             => config('services.mercadopago.currency', 'ARS'),
                'provider'           => 'mercadopago',
                'mp_payment_id'      => $paymentId,
                'mp_status'          => $status,
                'mp_status_detail'   => $statusDetail,
                'mp_payment_type'    => (string) ($payment->payment_type_id ?? ''),
                'mp_payment_method'  => (string) ($payment->payment_method_id ?? ''),
                'detalle_externo'    => $detalle,
                'external_reference' => $externalRef,
                'fecha_aprobado'     => $status === 'approved' ? Carbon::now() : null,
                'estado'             => match ($status) {
                    'approved' => Pago::ESTADO_APROBADO,
                    'rejected' => Pago::ESTADO_RECHAZADO,
                    default    => Pago::ESTADO_PENDIENTE,
                },
            ]
        );

        if ($status === 'approved') {
            $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]); // clase pagada
            return [
                'titulo'  => 'Pago aprobado',
                'mensaje' => '¡Listo! Se aprobó el pago y el turno quedó como Clase pagada.',
            ];
        }

        if ($turno->estado !== Turno::ESTADO_PENDIENTE_PAGO) {
            $turno->update(['estado' => Turno::ESTADO_PENDIENTE_PAGO]);
        }

        return [
            'titulo' => $status === 'rejected' ? 'Pago rechazado' : 'Pago pendiente',
            'mensaje' => $status === 'rejected'
                ? 'El pago fue rechazado. Podés intentar nuevamente.'
                : 'El pago quedó pendiente. Cuando se apruebe, se actualizará automáticamente.',
        ];
    }
}
