<?php

namespace App\Filament\Alumno\Pages;

use App\Models\Credito;
use App\Services\CreditoService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MisCreditos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Mis créditos';

    protected static ?string $title = 'Mis créditos';

    protected static ?string $slug = 'mis-creditos';

    protected string $view = 'filament.alumno.pages.mis-creditos';

    public string $saldoDisponible = '0.00';

    /** @var array<int, array<string, int|string>> */
    public array $historial = [];

    public function mount(CreditoService $creditoService): void
    {
        $alumnoId = (int) Auth::id();

        $this->saldoDisponible = $creditoService->saldoDisponible($alumnoId);

        $this->historial = Credito::query()
            ->where('alumno_id', $alumnoId)
            ->with('turno:id,fecha,hora_inicio,hora_fin')
            ->orderByDesc('cancelado_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Credito $credito) => [
                'id' => $credito->id,
                'turno_id' => $credito->turno_id,
                'fecha' => $credito->cancelado_at->format('d/m/Y H:i'),
                'turno_fecha' => $credito->turno?->fecha?->format('d/m/Y') ?? '-',
                'turno_horario' => $credito->turno
                    ? substr((string) $credito->turno->hora_inicio, 0, 5)
                        .' - '.substr((string) $credito->turno->hora_fin, 0, 5)
                    : '-',
                'importe_pagado' => $credito->importe_pagado,
                'importe_credito' => $credito->importe_credito,
                'importe_penalizacion' => $credito->importe_penalizacion,
                'estado' => $credito->estado,
                'estado_label' => match ($credito->estado) {
                    Credito::ESTADO_ESPERANDO_PAGO => 'Esperando pago',
                    Credito::ESTADO_DISPONIBLE => 'Disponible',
                    Credito::ESTADO_NO_APLICA => 'No aplica',
                    default => ucfirst(str_replace('_', ' ', $credito->estado)),
                },
                'porcentaje_credito' => $credito->porcentaje_credito_aplicado,
                'porcentaje_penalizacion' => $credito->porcentaje_penalizacion_aplicado,
                'horas_limite' => $credito->horas_limite_aplicadas,
            ])
            ->all();
    }
}
