<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Turno;
use App\Services\AuditLogger;
use App\Services\MercadoPagoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoController extends Controller
{
    /**
     * Pago desde el panel (botón "Pagar") - requiere login
     */
    public function pagar(Turno $turno, MercadoPagoService $mp, AuditLogger $audit)
    {
        return DB::transaction(function () use ($turno, $mp, $audit) {
            $turno = Turno::query()
                ->whereKey($turno->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($turno->estado === Turno::ESTADO_CANCELADO) {
                abort(403, 'Este turno está cancelado.');
            }

            if ($turno->estado === Turno::ESTADO_CONFIRMADO) {
                $audit->log('pago.intento_bloqueado_turno_confirmado', $turno, [
                    'turno_id' => $turno->id,
                    'estado_turno' => $turno->estado,
                ]);

                return view('turnos.confirmacion-resultado', [
                    'titulo'  => 'Ya está pagado',
                    'mensaje' => 'Este turno ya figura como Clase pagada.',
                ]);
            }

            $pagoExistente = $turno->pago;

            if ($pagoExistente && $pagoExistente->estado === Pago::ESTADO_APROBADO) {
                if (
                    $turno->estado !== Turno::ESTADO_CANCELADO &&
                    $turno->estado !== Turno::ESTADO_CONFIRMADO
                ) {
                    $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]);
                }

                $audit->log('pago.intento_bloqueado_pago_aprobado', $turno, [
                    'turno_id' => $turno->id,
                    'pago_id' => $pagoExistente->pago_id ?? null,
                    'mp_payment_id' => $pagoExistente->mp_payment_id ?? null,
                ]);

                return view('turnos.confirmacion-resultado', [
                    'titulo'  => 'Ya está pagado',
                    'mensaje' => 'Ya registramos un pago aprobado para este turno.',
                ]);
            }

            if ($turno->estado !== Turno::ESTADO_PENDIENTE_PAGO) {
                $audit->log('pago.intento_bloqueado_estado_invalido', $turno, [
                    'turno_id' => $turno->id,
                    'estado_turno' => $turno->estado,
                ]);

                abort(403, 'El turno no está pendiente de pago.');
            }

            if ($pagoExistente && $pagoExistente->mp_init_point) {
                $audit->log('pago.reuso_preference', $turno, [
                    'turno_id' => $turno->id,
                    'mp_init_point' => $pagoExistente->mp_init_point,
                    'mp_preference_id' => $pagoExistente->mp_preference_id,
                ]);

                return redirect()->away($pagoExistente->mp_init_point);
            }

            $pago = $mp->crearLinkDePagoParaTurno($turno);

            $audit->log('pago.link_creado', $turno, [
                'turno_id' => $turno->id,
                'pago_id' => $pago->pago_id ?? null,
                'monto' => (float) $pago->monto,
                'moneda' => $pago->moneda,
                'mp_preference_id' => $pago->mp_preference_id,
                'mp_init_point' => $pago->mp_init_point,
                'external_reference' => $pago->external_reference,
            ]);

            return redirect()->away($pago->mp_init_point);
        });
    }

    /**
     * Pago desde MAIL (link firmado + alumno_id).
     */
    public function pagarDesdeMail(Request $request, Turno $turno, MercadoPagoService $mp, AuditLogger $audit)
    {
        $alumnoId = (int) $request->query('alumno_id');

        if (! $alumnoId) {
            abort(403, 'Link inválido (falta alumno_id).');
        }

        if ((int) $turno->alumno_id !== $alumnoId) {
            abort(403, 'No autorizado.');
        }

        return DB::transaction(function () use ($turno, $mp, $audit) {
            $turno = Turno::query()
                ->whereKey($turno->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($turno->estado === Turno::ESTADO_CANCELADO) {
                return view('turnos.confirmacion-resultado', [
                    'titulo'  => 'No disponible',
                    'mensaje' => 'Este turno está cancelado.',
                ]);
            }

            if ($turno->estado === Turno::ESTADO_CONFIRMADO) {
                $audit->log('pago.mail_intento_bloqueado_turno_confirmado', $turno, [
                    'turno_id' => $turno->id,
                ]);

                return view('turnos.confirmacion-resultado', [
                    'titulo'  => 'Ya está pagado',
                    'mensaje' => 'Este turno ya figura como Clase pagada.',
                ]);
            }

            $pagoExistente = $turno->pago;

            if ($pagoExistente && $pagoExistente->estado === Pago::ESTADO_APROBADO) {
                if (
                    $turno->estado !== Turno::ESTADO_CANCELADO &&
                    $turno->estado !== Turno::ESTADO_CONFIRMADO
                ) {
                    $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]);
                }

                $audit->log('pago.mail_intento_bloqueado_pago_aprobado', $turno, [
                    'turno_id' => $turno->id,
                    'pago_id' => $pagoExistente->pago_id ?? null,
                    'mp_payment_id' => $pagoExistente->mp_payment_id ?? null,
                ]);

                return view('turnos.confirmacion-resultado', [
                    'titulo'  => 'Ya está pagado',
                    'mensaje' => 'Ya registramos un pago aprobado para este turno.',
                ]);
            }

            if ($turno->estado !== Turno::ESTADO_PENDIENTE_PAGO) {
                $audit->log('pago.mail_intento_estado_invalido', $turno, [
                    'turno_id' => $turno->id,
                    'estado_turno' => $turno->estado,
                ]);

                return view('turnos.confirmacion-resultado', [
                    'titulo'  => 'No disponible',
                    'mensaje' => 'Este turno no está pendiente de pago.',
                ]);
            }

            if ($pagoExistente && $pagoExistente->mp_init_point) {
                $audit->log('pago.mail_reuso_preference', $turno, [
                    'turno_id' => $turno->id,
                    'mp_init_point' => $pagoExistente->mp_init_point,
                ]);

                return redirect()->away($pagoExistente->mp_init_point);
            }

            $pago = $mp->crearLinkDePagoParaTurno($turno);

            $audit->log('pago.link_creado', $turno, [
                'turno_id' => $turno->id,
                'pago_id' => $pago->pago_id ?? null,
                'monto' => (float) $pago->monto,
                'moneda' => $pago->moneda,
                'mp_preference_id' => $pago->mp_preference_id,
                'mp_init_point' => $pago->mp_init_point,
            ]);

            return redirect()->away($pago->mp_init_point);
        });
    }

    public function success(Request $request, Turno $turno)
    {
        $paymentId = (string) $request->query('payment_id', '');

        if ($paymentId !== '') {
            $this->procesarPagoDesdeMercadoPago($paymentId, $turno);
        }

        return redirect('/alumno/turnos');
    }

    public function failure(Request $request, Turno $turno)
    {
        $paymentId = (string) $request->query('payment_id', '');

        if ($paymentId !== '') {
            $this->procesarPagoDesdeMercadoPago($paymentId, $turno);
        }

        return view('turnos.confirmacion-resultado', [
            'titulo'  => 'Pago fallido o cancelado',
            'mensaje' => 'El pago fue cancelado o rechazado. Podés intentar de nuevo desde tu panel.',
        ]);
    }

    public function pending(Request $request, Turno $turno)
    {
        $paymentId = (string) $request->query('payment_id', '');

        if ($paymentId !== '') {
            $this->procesarPagoDesdeMercadoPago($paymentId, $turno);
        }

        return view('turnos.confirmacion-resultado', [
            'titulo'  => 'Pago pendiente',
            'mensaje' => 'El pago quedó pendiente. Cuando se apruebe, se actualizará automáticamente.',
        ]);
    }

    /**
     * Webhook Mercado Pago
     */
    public function webhook(Request $request, AuditLogger $audit)
    {
        Log::info('Webhook MP recibido', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        $audit->log('pago.webhook_recibido', null, [
            'payload' => $request->all(),
        ], null);

        if ($this->debeIgnorarWebhook($request)) {
            return response()->json(['ok' => true, 'ignored' => true, 'reason' => 'non_payment_event']);
        }

        $paymentId = $this->extraerPaymentIdDesdeWebhook($request);

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

                $audit->log('pago.webhook_sin_turno', null, [
                    'payment_id' => (string) $paymentId,
                    'external_reference' => $externalRef,
                ]);

                return response()->json(['ok' => true, 'ignored' => true, 'reason' => 'no_turno_id']);
            }

            return DB::transaction(function () use ($payment, $turnoId, $audit) {
                $turno = Turno::query()
                    ->whereKey($turnoId)
                    ->lockForUpdate()
                    ->first();

                if (! $turno) {
                    $audit->log('pago.webhook_turno_no_encontrado', null, [
                        'turno_id' => $turnoId,
                    ]);

                    return response()->json(['ok' => true, 'turno_not_found' => true]);
                }

                $res = $this->procesarPagoDesdeObjetoMP($payment, $turno, $audit);

                return response()->json(['ok' => true, 'status' => $res['status'] ?? null]);
            });
        } catch (\Throwable $e) {
            Log::error('Webhook MP error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            $audit->log('pago.webhook_error', null, [
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

            return DB::transaction(function () use ($payment, $turno) {
                $turnoBloqueado = Turno::query()
                    ->whereKey($turno->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                return $this->procesarPagoDesdeObjetoMP($payment, $turnoBloqueado, null);
            });
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

    /**
     * Procesa pago desde objeto MP.
     * Si $audit es null, no registra auditoría.
     */
    private function procesarPagoDesdeObjetoMP(object $payment, Turno $turno, ?AuditLogger $audit): array
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

            if ($audit) {
                $audit->log('pago.external_reference_invalida', $turno, [
                    'turno_id' => $turno->id,
                    'payment_id' => $paymentId,
                    'external_reference' => $externalRef,
                ]);
            }

            return [
                'titulo'  => 'Pago inválido',
                'mensaje' => 'El pago no corresponde a este turno.',
                'status'  => 'invalid',
            ];
        }

        $pagoExistente = $turno->pago;

        if ($pagoExistente && $pagoExistente->estado === Pago::ESTADO_APROBADO) {
            if (
                ! empty($pagoExistente->mp_payment_id) &&
                $pagoExistente->mp_payment_id !== $paymentId
            ) {
                Log::warning('Conflicto de mp_payment_id para turno ya aprobado', [
                    'turno_id' => $turno->id,
                    'pago_id' => $pagoExistente->pago_id,
                    'mp_payment_id_guardado' => $pagoExistente->mp_payment_id,
                    'mp_payment_id_recibido' => $paymentId,
                ]);

                if ($audit) {
                    $audit->log('pago.payment_id_conflicto', $turno, [
                        'turno_id' => $turno->id,
                        'pago_id' => $pagoExistente->pago_id,
                        'mp_payment_id_guardado' => $pagoExistente->mp_payment_id,
                        'mp_payment_id_recibido' => $paymentId,
                        'mp_status_recibido' => $status,
                        'mp_status_detail_recibido' => $statusDetail,
                    ]);
                }
            }

            if (
                $turno->estado !== Turno::ESTADO_CANCELADO &&
                $turno->estado !== Turno::ESTADO_CONFIRMADO
            ) {
                $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]);
            }

            return [
                'titulo'  => 'Pago aprobado',
                'mensaje' => 'El pago ya había sido procesado para este turno.',
                'status'  => 'approved',
            ];
        }

        $detalle = json_decode(json_encode($payment), true);

        $payloadPago = [
            'turno_id'            => $turno->id,
            'monto'               => $turno->precio_total,
            'moneda'              => config('services.mercadopago.currency', 'ARS'),
            'provider'            => 'mercadopago',
            'mp_payment_id'       => $paymentId,
            'mp_status'           => $status,
            'mp_status_detail'    => $statusDetail,
            'mp_payment_type'     => (string) ($payment->payment_type_id ?? ''),
            'mp_payment_method'   => (string) ($payment->payment_method_id ?? ''),
            'detalle_externo'     => $detalle,
            'external_reference'  => $externalRef,
            'fecha_aprobado'      => $status === 'approved' ? Carbon::now() : null,
            'estado'              => match ($status) {
                'approved' => Pago::ESTADO_APROBADO,
                'rejected' => Pago::ESTADO_RECHAZADO,
                default    => Pago::ESTADO_PENDIENTE,
            },
        ];

        if ($pagoExistente) {
            $pagoExistente->fill($payloadPago);
            $pagoExistente->save();
            $pago = $pagoExistente;
        } else {
            $pago = Pago::create($payloadPago);
        }

        if ($audit) {
            $audit->log('pago.estado_actualizado', $turno, [
                'turno_id' => $turno->id,
                'pago_id' => $pago->pago_id ?? null,
                'mp_payment_id' => $paymentId,
                'mp_status' => $status,
                'mp_status_detail' => $statusDetail,
                'estado_pago' => $pago->estado,
            ]);
        }

        if ($status === 'approved') {
            if ($turno->estado === Turno::ESTADO_CANCELADO) {
                if ($audit) {
                    $audit->log('pago.aprobado_turno_cancelado_no_se_confirma', $turno, [
                        'turno_id' => $turno->id,
                        'mp_payment_id' => $paymentId,
                        'estado_turno' => $turno->estado,
                        'cancelacion_tipo' => $turno->cancelacion_tipo,
                        'cancelado_at' => $turno->cancelado_at,
                    ]);
                }

                return [
                    'titulo'  => 'Pago aprobado',
                    'mensaje' => 'El pago se aprobó, pero el turno ya estaba cancelado. El estado del turno no se modificó.',
                    'status'  => 'approved',
                ];
            }

            if ($turno->estado !== Turno::ESTADO_CONFIRMADO) {
                $estadoAntes = $turno->estado;
                $turno->update(['estado' => Turno::ESTADO_CONFIRMADO]);

                if ($audit) {
                    $audit->log('pago.aprobado', $turno, [
                        'turno_id' => $turno->id,
                        'estado_turno_anterior' => $estadoAntes,
                        'estado_turno_nuevo' => Turno::ESTADO_CONFIRMADO,
                        'mp_payment_id' => $paymentId,
                        'monto' => (float) $turno->precio_total,
                    ]);
                }
            }

            return [
                'titulo'  => 'Pago aprobado',
                'mensaje' => '¡Listo! Se aprobó el pago y el turno quedó como Clase pagada.',
                'status'  => 'approved',
            ];
        }

        if (in_array($turno->estado, [
            Turno::ESTADO_CANCELADO,
            Turno::ESTADO_VENCIDO,
            Turno::ESTADO_CONFIRMADO,
        ], true)) {
            return [
                'titulo'  => $status === 'rejected' ? 'Pago rechazado' : 'Pago pendiente',
                'mensaje' => $status === 'rejected'
                    ? 'El pago fue rechazado, pero el turno ya tiene un estado final y no se modificó.'
                    : 'El pago quedó pendiente, pero el turno ya tiene un estado final y no se modificó.',
                'status'  => $status ?: 'pending',
            ];
        }

        return [
            'titulo'  => $status === 'rejected' ? 'Pago rechazado' : 'Pago pendiente',
            'mensaje' => $status === 'rejected'
                ? 'Mercado Pago rechazó el pago. Podés intentar nuevamente desde tu panel.'
                : 'El pago quedó pendiente. Cuando se acredite, lo actualizaremos.',
            'status'  => $status ?: 'pending',
        ];
    }

    private function debeIgnorarWebhook(Request $request): bool
    {
        $type = (string) ($request->query('type')
            ?? $request->query('topic')
            ?? data_get($request->all(), 'type')
            ?? data_get($request->all(), 'topic')
            ?? '');

        $action = (string) data_get($request->all(), 'action', '');

        if ($type === 'payment') {
            return false;
        }

        if (str_contains($action, 'payment')) {
            return false;
        }

        return true;
    }

    private function extraerPaymentIdDesdeWebhook(Request $request): ?string
    {
        $candidatos = [
            $request->query('data_id'),
            $request->query('id'),
            data_get($request->all(), 'data.id'),
            data_get($request->all(), 'resource.id'),
            data_get($request->all(), 'id'),
        ];

        foreach ($candidatos as $valor) {
            if ($valor !== null && $valor !== '') {
                return (string) $valor;
            }
        }

        $resource = (string) data_get($request->all(), 'resource', '');
        if ($resource !== '' && preg_match('~/v1/payments/(\d+)~', $resource, $m)) {
            return (string) $m[1];
        }

        return null;
    }
}