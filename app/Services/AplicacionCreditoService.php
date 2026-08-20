<?php

namespace App\Services;

use App\Models\Credito;
use App\Models\CreditoAplicacion;
use App\Models\Pago;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AplicacionCreditoService
{
    /**
     * @return array{
     *     precio_total:string,
     *     credito_disponible:string,
     *     credito_aplicable:string,
     *     diferencia:string,
     *     cubre_total:bool
     * }
     */
    public function previsualizar(Turno $turno, int $alumnoId): array
    {
        $turnoActual = Turno::query()->findOrFail($turno->getKey());

        $this->validarTurnoDelAlumnoPendienteDePago($turnoActual, $alumnoId);

        $precioCentavos = $this->aCentavos((string) $turnoActual->precio_total, 'precio_total');

        if ($precioCentavos <= 0) {
            throw ValidationException::withMessages([
                'credito' => 'El turno no tiene un precio total válido.',
            ]);
        }

        $saldoCentavos = $this->creditosElegibles($alumnoId)
            ->get(['id', 'saldo_disponible'])
            ->sum(fn (Credito $credito): int => $this->aCentavos(
                (string) $credito->saldo_disponible,
                'saldo_disponible',
            ));

        $aplicableCentavos = min($precioCentavos, $saldoCentavos);

        return [
            'precio_total' => $this->desdeCentavos($precioCentavos),
            'credito_disponible' => $this->desdeCentavos($saldoCentavos),
            'credito_aplicable' => $this->desdeCentavos($aplicableCentavos),
            'diferencia' => $this->desdeCentavos($precioCentavos - $aplicableCentavos),
            'cubre_total' => $saldoCentavos >= $precioCentavos,
        ];
    }

    public function pagarTotalmenteConCredito(Turno $turno, int $alumnoId): Pago
    {
        return DB::transaction(function () use ($turno, $alumnoId): Pago {
            $turnoBloqueado = Turno::query()
                ->whereKey($turno->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $turnoBloqueado->alumno_id !== $alumnoId) {
                abort(404);
            }

            $pagoExistente = Pago::query()
                ->where('turno_id', $turnoBloqueado->id)
                ->lockForUpdate()
                ->first();

            $aplicacionesExistentes = CreditoAplicacion::query()
                ->where('turno_id', $turnoBloqueado->id)
                ->lockForUpdate()
                ->get();

            if (
                $pagoExistente
                && $pagoExistente->estado === Pago::ESTADO_APROBADO
                && $pagoExistente->provider === 'credito'
                && $turnoBloqueado->estado === Turno::ESTADO_CONFIRMADO
                && $aplicacionesExistentes->isNotEmpty()
                && $aplicacionesExistentes->every(
                    fn (CreditoAplicacion $aplicacion): bool =>
                        $aplicacion->estado === CreditoAplicacion::ESTADO_APLICADO
                )
            ) {
                return $pagoExistente;
            }

            $this->validarTurnoDelAlumnoPendienteDePago($turnoBloqueado, $alumnoId);

            if ($pagoExistente || $aplicacionesExistentes->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'credito' => 'El pago de este turno ya fue iniciado.',
                ]);
            }

            $precioCentavos = $this->aCentavos((string) $turnoBloqueado->precio_total, 'precio_total');

            if ($precioCentavos <= 0) {
                throw ValidationException::withMessages([
                    'credito' => 'El turno no tiene un precio total válido.',
                ]);
            }

            $creditos = $this->creditosElegibles($alumnoId)
                ->lockForUpdate()
                ->get();

            $saldoTotalCentavos = $creditos->sum(
                fn (Credito $credito): int => $this->aCentavos(
                    (string) $credito->saldo_disponible,
                    'saldo_disponible',
                )
            );

            if ($saldoTotalCentavos < $precioCentavos) {
                throw ValidationException::withMessages([
                    'credito' => 'El crédito disponible no alcanza para cubrir la totalidad de la clase.',
                ]);
            }

            $restanteCentavos = $precioCentavos;

            foreach ($creditos as $credito) {
                if ($restanteCentavos === 0) {
                    break;
                }

                $saldoCreditoCentavos = $this->aCentavos(
                    (string) $credito->saldo_disponible,
                    'saldo_disponible',
                );
                $importeAplicadoCentavos = min($saldoCreditoCentavos, $restanteCentavos);

                if ($importeAplicadoCentavos <= 0) {
                    continue;
                }

                $credito->update([
                    'saldo_disponible' => $this->desdeCentavos(
                        $saldoCreditoCentavos - $importeAplicadoCentavos,
                    ),
                ]);

                CreditoAplicacion::create([
                    'credito_id' => $credito->id,
                    'turno_id' => $turnoBloqueado->id,
                    'importe' => $this->desdeCentavos($importeAplicadoCentavos),
                    'estado' => CreditoAplicacion::ESTADO_APLICADO,
                    'idempotency_key' => "credito-aplicacion:{$credito->id}:turno:{$turnoBloqueado->id}",
                ]);

                $restanteCentavos -= $importeAplicadoCentavos;
            }

            if ($restanteCentavos !== 0) {
                throw ValidationException::withMessages([
                    'credito' => 'No se pudo completar la aplicación de los créditos.',
                ]);
            }

            $pago = Pago::create([
                'turno_id' => $turnoBloqueado->id,
                'monto' => $this->desdeCentavos($precioCentavos),
                'monto_mercadopago' => '0.00',
                'moneda' => config('services.mercadopago.currency', 'ARS'),
                'estado' => Pago::ESTADO_APROBADO,
                'provider' => 'credito',
                'external_reference' => "turno:{$turnoBloqueado->id}:intento:".Str::uuid(),
                'fecha_aprobado' => now(),
            ]);

            $turnoBloqueado->update([
                'estado' => Turno::ESTADO_CONFIRMADO,
            ]);

            return $pago;
        });
    }

    /**
     * Reserva el crédito disponible y congela la composición del intento mixto.
     *
     * @return array{precio_total:string, credito_reservado:string, monto_mercadopago:string, external_reference:string}
     */
    public function reservarParaPagoParcial(Turno $turno, int $alumnoId): array
    {
        return DB::transaction(function () use ($turno, $alumnoId): array {
            $turnoBloqueado = Turno::query()
                ->whereKey($turno->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->validarTurnoDelAlumnoPendienteDePago($turnoBloqueado, $alumnoId);

            $pago = Pago::query()
                ->where('turno_id', $turnoBloqueado->id)
                ->lockForUpdate()
                ->first();

            $aplicaciones = CreditoAplicacion::query()
                ->where('turno_id', $turnoBloqueado->id)
                ->lockForUpdate()
                ->get();

            if ($aplicaciones->contains('estado', CreditoAplicacion::ESTADO_APLICADO)) {
                throw ValidationException::withMessages([
                    'credito' => 'Este turno ya tiene crédito aplicado.',
                ]);
            }

            $reservadas = $aplicaciones->where('estado', CreditoAplicacion::ESTADO_RESERVADO);

            if ($reservadas->isNotEmpty()) {
                if (! $pago || $pago->estado !== Pago::ESTADO_PENDIENTE) {
                    throw ValidationException::withMessages([
                        'credito' => 'El intento de pago con crédito no es válido.',
                    ]);
                }

                return [
                    'precio_total' => (string) $pago->monto,
                    'credito_reservado' => $this->desdeCentavos($reservadas->sum(
                        fn (CreditoAplicacion $aplicacion): int => $this->aCentavos((string) $aplicacion->importe, 'importe'),
                    )),
                    'monto_mercadopago' => (string) $pago->monto_mercadopago,
                    'external_reference' => (string) $pago->external_reference,
                ];
            }

            if (
                $pago
                && $pago->estado === Pago::ESTADO_PENDIENTE
                && ($pago->mp_preference_id || $pago->mp_init_point)
            ) {
                throw ValidationException::withMessages([
                    'credito' => 'Ya existe un intento de pago por el importe completo. Continuá con esa preferencia o esperá su resultado.',
                ]);
            }

            $precioCentavos = $this->aCentavos((string) $turnoBloqueado->precio_total, 'precio_total');
            $creditos = $this->creditosElegibles($alumnoId)->lockForUpdate()->get();
            $saldoCentavos = $creditos->sum(
                fn (Credito $credito): int => $this->aCentavos((string) $credito->saldo_disponible, 'saldo_disponible'),
            );

            if ($saldoCentavos <= 0 || $saldoCentavos >= $precioCentavos) {
                throw ValidationException::withMessages([
                    'credito' => 'El pago parcial requiere un crédito mayor que cero y menor que el precio total.',
                ]);
            }

            $restanteCentavos = $saldoCentavos;

            foreach ($creditos as $credito) {
                if ($restanteCentavos === 0) {
                    break;
                }

                $saldoCreditoCentavos = $this->aCentavos((string) $credito->saldo_disponible, 'saldo_disponible');
                $importeCentavos = min($saldoCreditoCentavos, $restanteCentavos);

                if ($importeCentavos <= 0) {
                    continue;
                }

                $credito->update([
                    'saldo_disponible' => $this->desdeCentavos($saldoCreditoCentavos - $importeCentavos),
                ]);

                CreditoAplicacion::updateOrCreate(
                    ['credito_id' => $credito->id, 'turno_id' => $turnoBloqueado->id],
                    [
                        'importe' => $this->desdeCentavos($importeCentavos),
                        'estado' => CreditoAplicacion::ESTADO_RESERVADO,
                        'idempotency_key' => "credito-aplicacion:{$credito->id}:turno:{$turnoBloqueado->id}",
                    ],
                );

                $restanteCentavos -= $importeCentavos;
            }

            $montoMercadoPagoCentavos = $precioCentavos - $saldoCentavos;
            $externalReference = "turno:{$turnoBloqueado->id}:intento:".Str::uuid();

            $datosPago = [
                'monto' => $this->desdeCentavos($precioCentavos),
                'monto_mercadopago' => $this->desdeCentavos($montoMercadoPagoCentavos),
                'moneda' => config('services.mercadopago.currency', 'ARS'),
                'estado' => Pago::ESTADO_PENDIENTE,
                'provider' => 'mercadopago',
                'mp_preference_id' => null,
                'mp_init_point' => null,
                'mp_payment_id' => null,
                'mp_status' => null,
                'mp_status_detail' => null,
                'mp_payment_type' => null,
                'mp_payment_method' => null,
                'detalle_externo' => null,
                'external_reference' => $externalReference,
                'fecha_aprobado' => null,
            ];

            if ($pago) {
                $pago->update($datosPago);
            } else {
                Pago::create(['turno_id' => $turnoBloqueado->id] + $datosPago);
            }

            return [
                'precio_total' => $this->desdeCentavos($precioCentavos),
                'credito_reservado' => $this->desdeCentavos($saldoCentavos),
                'monto_mercadopago' => $this->desdeCentavos($montoMercadoPagoCentavos),
                'external_reference' => $externalReference,
            ];
        });
    }

    public function aplicarReservas(Turno $turno): void
    {
        CreditoAplicacion::query()
            ->where('turno_id', $turno->getKey())
            ->where('estado', CreditoAplicacion::ESTADO_RESERVADO)
            ->lockForUpdate()
            ->get()
            ->each->update(['estado' => CreditoAplicacion::ESTADO_APLICADO]);
    }

    public function liberarReservas(Turno $turno): void
    {
        $aplicaciones = CreditoAplicacion::query()
            ->where('turno_id', $turno->getKey())
            ->where('estado', CreditoAplicacion::ESTADO_RESERVADO)
            ->lockForUpdate()
            ->get();

        foreach ($aplicaciones as $aplicacion) {
            $credito = Credito::query()->whereKey($aplicacion->credito_id)->lockForUpdate()->firstOrFail();
            $saldoCentavos = $this->aCentavos((string) $credito->saldo_disponible, 'saldo_disponible');
            $importeCentavos = $this->aCentavos((string) $aplicacion->importe, 'importe');

            $credito->update([
                'saldo_disponible' => $this->desdeCentavos($saldoCentavos + $importeCentavos),
            ]);
            $aplicacion->update(['estado' => CreditoAplicacion::ESTADO_LIBERADO]);
        }
    }

    private function creditosElegibles(int $alumnoId): Builder
    {
        return Credito::query()
            ->where('alumno_id', $alumnoId)
            ->where('estado', Credito::ESTADO_DISPONIBLE)
            ->where('saldo_disponible', '>', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('vence_at')
                    ->orWhere('vence_at', '>', now());
            })
            ->orderByRaw('CASE WHEN vence_at IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('vence_at')
            ->orderBy('created_at')
            ->orderBy('id');
    }

    private function validarTurnoDelAlumnoPendienteDePago(Turno $turno, int $alumnoId): void
    {
        if ((int) $turno->alumno_id !== $alumnoId) {
            abort(404);
        }

        if ($turno->estado !== Turno::ESTADO_PENDIENTE_PAGO) {
            throw ValidationException::withMessages([
                'credito' => 'El turno no está pendiente de pago.',
            ]);
        }

        if ($turno->inicioDateTime()->isPast()) {
            throw ValidationException::withMessages([
                'credito' => 'La clase ya comenzó o venció.',
            ]);
        }
    }

    private function aCentavos(string $importe, string $campo): int
    {
        $importe = trim($importe);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $importe)) {
            throw ValidationException::withMessages([
                'credito' => "El campo {$campo} no contiene un importe válido.",
            ]);
        }

        [$entero, $decimales] = array_pad(explode('.', $importe, 2), 2, '');
        $decimales = str_pad($decimales, 2, '0');

        return ((int) $entero * 100) + (int) $decimales;
    }

    private function desdeCentavos(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
