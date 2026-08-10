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
        $vigenciaDias = $politica->vigencia_creditos_dias;
        $porcentajeProfesorPenalizacion = $politica->porcentaje_profesor_penalizacion;
        $porcentajePlataformaPenalizacion = $politica->porcentaje_plataforma_penalizacion;

        if (
            $horas < 0
            || $vigenciaDias === null
            || $vigenciaDias < 1
            || $porcentajeProfesorPenalizacion === null
            || $porcentajePlataformaPenalizacion === null
        ) {
            $this->politicaInvalida();
        }

        $porcentajeProfesorPenalizacion = (float) $porcentajeProfesorPenalizacion;
        $porcentajePlataformaPenalizacion = (float) $porcentajePlataformaPenalizacion;

        foreach ([
            $creditoAnticipado,
            $creditoTardio,
            $penalizacionTardia,
            $porcentajeProfesorPenalizacion,
            $porcentajePlataformaPenalizacion,
        ] as $porcentaje) {
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

        if (
            abs(
                ($porcentajeProfesorPenalizacion + $porcentajePlataformaPenalizacion)
                - self::PORCENTAJE_MAXIMO
            ) > 0.001
        ) {
            $this->politicaInvalida();
        }

        return $politica;
    }

    public function huellaPolitica(PoliticaCancelacion $politica): string
    {
        return hash('sha256', json_encode([
            'id' => (int) $politica->id,
            'codigo' => (string) $politica->codigo,
            'horas_cancelacion_sin_penalizacion' => (int) $politica->horas_cancelacion_sin_penalizacion,
            'porcentaje_credito_anticipado' => (string) $politica->porcentaje_credito_anticipado,
            'porcentaje_credito_tardio' => (string) $politica->porcentaje_credito_tardio,
            'porcentaje_penalizacion_tardia' => (string) $politica->porcentaje_penalizacion_tardia,
            'vigencia_creditos_dias' => (int) $politica->vigencia_creditos_dias,
            'porcentaje_profesor_penalizacion' => (string) $politica->porcentaje_profesor_penalizacion,
            'porcentaje_plataforma_penalizacion' => (string) $politica->porcentaje_plataforma_penalizacion,
            'updated_at' => $politica->updated_at?->format('Y-m-d H:i:s.uP'),
        ], JSON_THROW_ON_ERROR));
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

        $porcentajeProfesorPenalizacion = (float) $politica->porcentaje_profesor_penalizacion;
        $porcentajePlataformaPenalizacion = (float) $politica->porcentaje_plataforma_penalizacion;
        $repartoPenalizacion = $this->calcularRepartoPenalizacion(
            (float) $importes['importe_penalizacion'],
            $porcentajeProfesorPenalizacion,
        );

        $vigenciaDias = (int) $politica->vigencia_creditos_dias;
        $venceAt = $estado === Credito::ESTADO_DISPONIBLE
            ? now()->addDays($vigenciaDias)
            : null;

        return Credito::create([
            'alumno_id' => $turno->alumno_id,
            'turno_id' => $turno->id,
            'pago_id' => $pago?->pago_id,
            ...$importes,
            ...$repartoPenalizacion,
            'porcentaje_credito_aplicado' => $porcentajeCredito,
            'porcentaje_penalizacion_aplicado' => $porcentajePenalizacion,
            'porcentaje_profesor_penalizacion_aplicado' => $porcentajeProfesorPenalizacion,
            'porcentaje_plataforma_penalizacion_aplicado' => $porcentajePlataformaPenalizacion,
            'horas_limite_aplicadas' => $politica->horas_cancelacion_sin_penalizacion,
            'vigencia_dias_aplicada' => $vigenciaDias,
            'estado' => $estado,
            'idempotency_key' => "credito-cancelacion-alumno:{$turno->id}",
            'cancelado_at' => $turno->cancelado_at ?? now(),
            'vence_at' => $venceAt,
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

            if ($credito->vigencia_dias_aplicada === null || $credito->vigencia_dias_aplicada < 1) {
                throw ValidationException::withMessages([
                    'credito' => 'El crÃ©dito pendiente no tiene una vigencia vÃ¡lida.',
                ]);
            }

            $importes = $this->calcularImportes(
                $pagoBloqueado,
                (float) $credito->porcentaje_credito_aplicado,
            );

            $repartoPenalizacion = [];

            if (
                $credito->porcentaje_profesor_penalizacion_aplicado !== null
                && $credito->porcentaje_plataforma_penalizacion_aplicado !== null
            ) {
                $porcentajeProfesor = (float) $credito->porcentaje_profesor_penalizacion_aplicado;
                $porcentajePlataforma = (float) $credito->porcentaje_plataforma_penalizacion_aplicado;

                if (
                    $porcentajeProfesor < self::PORCENTAJE_MINIMO
                    || $porcentajeProfesor > self::PORCENTAJE_MAXIMO
                    || $porcentajePlataforma < self::PORCENTAJE_MINIMO
                    || $porcentajePlataforma > self::PORCENTAJE_MAXIMO
                    || abs(
                        ($porcentajeProfesor + $porcentajePlataforma)
                        - self::PORCENTAJE_MAXIMO
                    ) > 0.001
                ) {
                    throw ValidationException::withMessages([
                        'credito' => 'El reparto de la penalización del crédito es inválido.',
                    ]);
                }

                $repartoPenalizacion = $this->calcularRepartoPenalizacion(
                    (float) $importes['importe_penalizacion'],
                    $porcentajeProfesor,
                );
            }

            $credito->update([
                'pago_id' => $pagoBloqueado->pago_id,
                ...$importes,
                ...$repartoPenalizacion,
                'estado' => Credito::ESTADO_DISPONIBLE,
                'vence_at' => now()->addDays($credito->vigencia_dias_aplicada),
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
                ->where(function ($query) {
                    $query->whereNull('vence_at')
                        ->orWhere('vence_at', '>', now());
                })
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

    /** @return array{importe_penalizacion_profesor:float, importe_penalizacion_plataforma:float} */
    private function calcularRepartoPenalizacion(
        float $importePenalizacion,
        float $porcentajeProfesor,
    ): array {
        $importeProfesor = round(
            $importePenalizacion * $porcentajeProfesor / self::PORCENTAJE_MAXIMO,
            2,
        );

        return [
            'importe_penalizacion_profesor' => $importeProfesor,
            'importe_penalizacion_plataforma' => round(
                $importePenalizacion - $importeProfesor,
                2,
            ),
        ];
    }

    private function politicaInvalida(): never
    {
        throw ValidationException::withMessages([
            'cancelacion' => 'La política de cancelación es inválida.',
        ]);
    }
}
