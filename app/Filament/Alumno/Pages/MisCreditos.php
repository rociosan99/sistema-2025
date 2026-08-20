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

    /** @var array<int, array<string, int|string|null>> */
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
                'saldo_disponible' => $credito->saldo_disponible,
                'importe_penalizacion' => $credito->importe_penalizacion,
                'vence_at' => $credito->vence_at?->format('d/m/Y H:i'),
                'estado_visual' => $this->estadoVisual($credito),
                'estado_label' => $this->estadoLabel($credito),
                'porcentaje_credito' => $credito->porcentaje_credito_aplicado,
                'porcentaje_penalizacion' => $credito->porcentaje_penalizacion_aplicado,
                'horas_limite' => $credito->horas_limite_aplicadas,
            ])
            ->all();
    }

    private function estadoVisual(Credito $credito): string
    {
        return match (true) {
            $credito->estado === Credito::ESTADO_ESPERANDO_PAGO => 'esperando_pago',
            $credito->estado === Credito::ESTADO_NO_APLICA => 'no_aplica',
            $credito->estado === Credito::ESTADO_DISPONIBLE
                && (float) $credito->saldo_disponible <= 0 => 'utilizado',
            $credito->estado === Credito::ESTADO_DISPONIBLE && $credito->vence_at === null => 'sin_vencimiento',
            $credito->estado === Credito::ESTADO_DISPONIBLE && $credito->vence_at->isPast() => 'vencido',
            $credito->estado === Credito::ESTADO_DISPONIBLE => 'disponible',
            default => 'no_aplica',
        };
    }

    private function estadoLabel(Credito $credito): string
    {
        return match ($this->estadoVisual($credito)) {
            'esperando_pago' => 'Esperando pago',
            'utilizado' => 'Utilizado',
            'disponible' => 'Disponible',
            'vencido' => 'Vencido',
            'sin_vencimiento' => 'Sin vencimiento',
            default => 'No corresponde',
        };
    }
}
