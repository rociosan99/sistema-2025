<?php

namespace App\Services;

use App\Models\Credito;
use App\Models\Pago;
use App\Models\PoliticaCancelacion;
use App\Models\Turno;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditoService
{
    private const PORCENTAJE_MINIMO = 0.0;

    private const PORCENTAJE_MAXIMO = 100.0;

    public function obtenerPoliticaVigente(): PoliticaCancelacion
    {
        $politica = PoliticaCancelacion::query()
            ->where('codigo', PoliticaCancelacion::CODIGO_CANCELACION_ALUMNO)
            ->first();

        if (! $politica) {
            throw ValidationException::withMessages([
                'cancelacion' => 'La política de cancelación no está configurada.',
            ]);
        }

        $horas = $politica->horas_cancelacion_sin_penalizacion;
        $creditoAnticipado = (float) $politica->porcentaje_credito_anticipado;
        $creditoTardio = (float) $politica->porcentaje_credito_tardio;
        $penalizacionTardia = (float) $politica->porcentaje_penalizacion_tardia;

        if ($horas < 0) {
            $this->politicaInvalida();
        }

        foreach ([$creditoAnticipado, $creditoTardio, $penalizacionTardia] as $porcentaje) {
            if (
                $porcentaje < self::PORCENTAJE_MINIMO
                || $porcentaje > self::PORCENTAJE_MAXIMO
            ) {
                $this->politicaInvalida();
            }
        }

        if (abs(($creditoTardio + $penalizacionTardia) - self::PORCENTAJE_MAXIMO) > 0.001) {
            $this->politicaInvalida();
        }

        return $politica;
    }

    public function registrarCancelacion(
        Turno $turno,
        PoliticaCancelacion $politica,
        bool $esAnticipada,
    ): Credito {
        $existente = Credito::query()
            ->where('turno_id', $turno->id)
            ->lockForUpdate()
            ->first();

        if ($existente) {
            return $existente;
        }

        $pago = Pago::query()
            ->where('turno_id', $turno->id)
            ->lockForUpdate()
            ->first();

        $porcentajeCredito = $esAnticipada
            ? (float) $politica->porcentaje_credito_anticipado
            : (float) $politica->porcentaje_credito_tardio;

        $porcentajePenalizacion = $esAnticipada
            ? self::PORCENTAJE_MAXIMO - $porcentajeCredito
            : (float) $politica->porcentaje_penalizacion_tardia;

        $estado = match (true) {
            $pago?->estado === Pago::ESTADO_APROBADO => Credito::ESTADO_DISPONIBLE,
            ! $pago => Credito::ESTADO_NO_APLICA,
            $pago->estado === Pago::ESTADO_RECHAZADO,
            $pago->estado === Pago::ESTADO_ERROR,
            $pago->mp_status === 'cancelled' => Credito::ESTADO_NO_APLICA,
            default => Credito::ESTADO_ESPERANDO_PAGO,
        };

        $importes = $estado === Credito::ESTADO_DISPONIBLE
            ? $this->calcularImportes($pago, $porcentajeCredito)
            : $this->importesEnCero();

        return Credito::create([
            'alumno_id' => $turno->alumno_id,
            'turno_id' => $turno->id,
            'pago_id' => $pago?->pago_id,
            ...$importes,
            'porcentaje_credito_aplicado' => $porcentajeCredito,
            'porcentaje_penalizacion_aplicado' => $porcentajePenalizacion,
            'horas_limite_aplicadas' => $politica->horas_cancelacion_sin_penalizacion,
            'estado' => $estado,
            'idempotency_key' => "credito-cancelacion-alumno:{$turno->id}",
            'cancelado_at' => $turno->cancelado_at ?? now(),
        ]);
    }

    public function completarPorPagoAprobado(Pago $pago): ?Credito
    {
        if ($pago->estado !== Pago::ESTADO_APROBADO) {
            return null;
        }

        return DB::transaction(function () use ($pago) {
            $pagoBloqueado = Pago::query()
                ->lockForUpdate()
                ->find($pago->pago_id);

            if (! $pagoBloqueado || $pagoBloqueado->estado !== Pago::ESTADO_APROBADO) {
                return null;
            }

            $credito = Credito::query()
                ->where('turno_id', $pagoBloqueado->turno_id)
                ->lockForUpdate()
                ->first();

            if (! $credito || $credito->estado !== Credito::ESTADO_ESPERANDO_PAGO) {
                return $credito;
            }

            if ($credito->pago_id !== null && (int) $credito->pago_id !== (int) $pagoBloqueado->pago_id) {
                throw ValidationException::withMessages([
                    'pago' => 'El crédito pendiente está asociado a otro pago.',
                ]);
            }

            $credito->update([
                'pago_id' => $pagoBloqueado->pago_id,
                ...$this->calcularImportes(
                    $pagoBloqueado,
                    (float) $credito->porcentaje_credito_aplicado,
                ),
                'estado' => Credito::ESTADO_DISPONIBLE,
            ]);

            return $credito->fresh();
        });
    }

    public function saldoDisponible(int $alumnoId): string
    {
        return number_format(
            (float) Credito::query()
                ->where('alumno_id', $alumnoId)
                ->where('estado', Credito::ESTADO_DISPONIBLE)
                ->sum('saldo_disponible'),
            2,
            '.',
            '',
        );
    }

    /** @return array{importe_pagado:float, importe_credito:float, importe_penalizacion:float, saldo_disponible:float} */
    private function calcularImportes(Pago $pago, float $porcentajeCredito): array
    {
        $importePagado = round((float) $pago->monto, 2);

        if ($importePagado <= 0) {
            throw ValidationException::withMessages([
                'pago' => 'El pago aprobado no tiene un importe válido.',
            ]);
        }

        $importeCredito = round(
            $importePagado * $porcentajeCredito / self::PORCENTAJE_MAXIMO,
            2,
        );

        $importePenalizacion = round($importePagado - $importeCredito, 2);

        return [
            'importe_pagado' => $importePagado,
            'importe_credito' => $importeCredito,
            'importe_penalizacion' => $importePenalizacion,
            'saldo_disponible' => $importeCredito,
        ];
    }

    /** @return array{importe_pagado:float, importe_credito:float, importe_penalizacion:float, saldo_disponible:float} */
    private function importesEnCero(): array
    {
        return [
            'importe_pagado' => 0.0,
            'importe_credito' => 0.0,
            'importe_penalizacion' => 0.0,
            'saldo_disponible' => 0.0,
        ];
    }

    private function politicaInvalida(): never
    {
        throw ValidationException::withMessages([
            'cancelacion' => 'La política de cancelación es inválida.',
        ]);
    }
}
