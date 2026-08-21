<?php

namespace App\Filament\Alumno\Pages;

use App\Models\Credito;
use App\Models\Turno;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class SuspensionCompletada extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $title = 'Clase suspendida correctamente';

    protected static ?string $slug = 'suspension-completada/{record}';

    protected string $view = 'filament.alumno.pages.suspension-completada';

    public Turno $turno;

    public ?Credito $credito = null;

    public bool $puedeReprogramar = false;

    public function mount(int|string $record): void
    {
        $this->turno = Turno::query()
            ->with([
                'materia:materia_id,materia_nombre',
                'profesor:id,name,apellido',
                'credito',
            ])
            ->findOrFail($record);

        abort_unless((int) $this->turno->alumno_id === (int) Auth::id(), 404);

        abort_unless(
            $this->turno->estado === Turno::ESTADO_CANCELADO
                && in_array($this->turno->cancelacion_tipo, ['sin_cargo', 'con_cargo'], true)
                && $this->turno->cancelado_at !== null
                && $this->turno->reprogramado_por_turno_id === null,
            404,
        );

        $this->credito = $this->turno->credito;
        $this->puedeReprogramar = $this->determinarSiPuedeReprogramar();
    }

    private function determinarSiPuedeReprogramar(): bool
    {
        if (
            $this->turno->cancelacion_tipo !== 'sin_cargo'
            || $this->turno->reprogramado_por_turno_id !== null
            || ! $this->credito
            || $this->credito->estado !== Credito::ESTADO_DISPONIBLE
            || ($this->credito->vence_at && $this->credito->vence_at->isPast())
        ) {
            return false;
        }

        $importePagadoCentavos = $this->aCentavos($this->credito->importe_pagado);
        $importeCreditoCentavos = $this->aCentavos($this->credito->importe_credito);
        $saldoDisponibleCentavos = $this->aCentavos($this->credito->saldo_disponible);
        $porcentajeCreditoCentésimos = (int) round(
            (float) $this->credito->porcentaje_credito_aplicado * 100,
        );

        if (
            $importePagadoCentavos <= 0
            || $importeCreditoCentavos !== $importePagadoCentavos
            || $saldoDisponibleCentavos !== $importeCreditoCentavos
            || $porcentajeCreditoCentésimos !== 10000
        ) {
            return false;
        }

        $horasRegla = (int) config('turnos.cancelacion_sin_cargo_horas', 24);
        $horasHastaInicio = now()->diffInHours($this->turno->inicioDateTime(), false);

        return $horasHastaInicio >= $horasRegla;
    }

    private function aCentavos(string|int|float|null $importe): int
    {
        return (int) round((float) $importe * 100);
    }
}
