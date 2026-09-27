<?php

namespace App\Filament\Alumno\Pages;

use App\Models\Credito;
use App\Services\CreditoService;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class MisCreditos extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Mis créditos';

    protected static ?string $title = 'Mis créditos';

    protected static ?string $slug = 'mis-creditos';

    protected string $view = 'filament.alumno.pages.mis-creditos';

    public string $saldoDisponible = '0.00';

    public ?string $estado = null;

    public ?string $desde = null;

    public ?string $hasta = null;

    public string $buscar = '';

    public int $porPagina = 10;

    public function mount(CreditoService $creditoService): void
    {
        $alumnoId = (int) Auth::id();

        $this->saldoDisponible = $creditoService->saldoDisponible($alumnoId);
    }

    protected function getViewData(): array
    {
        return [
            'historial' => $this->historialPaginado(),
            'estadoOptions' => $this->estadoOptions(),
        ];
    }

    public function aplicarFiltros(): void
    {
        $this->validate([
            'estado' => ['nullable', Rule::in(array_keys($this->estadoOptions()))],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'buscar' => ['nullable', 'string', 'max:50'],
            'porPagina' => ['required', Rule::in([10, 25, 50])],
        ], [
            'hasta.after_or_equal' => 'La fecha Hasta no puede ser anterior a Desde.',
        ]);

        $this->resetPage(pageName: 'creditosPage');
    }

    public function resetearFiltros(): void
    {
        $this->reset(['estado', 'desde', 'hasta', 'buscar']);
        $this->resetValidation();
        $this->resetPage(pageName: 'creditosPage');
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['estado', 'desde', 'hasta', 'buscar', 'porPagina'], true)) {
            $this->resetPage(pageName: 'creditosPage');
        }
    }

    public function historialPaginado(): LengthAwarePaginator
    {
        $alumnoId = (int) Auth::id();
        $estadosValidos = array_keys($this->estadoOptions());
        $porPagina = in_array($this->porPagina, [10, 25, 50], true) ? $this->porPagina : 10;
        $turnoBuscado = trim($this->buscar);

        /** @var Paginator $paginador */
        $paginador = Credito::query()
            ->where('alumno_id', $alumnoId)
            ->with('turno:id,fecha,hora_inicio,hora_fin')
            ->when(
                in_array($this->estado, $estadosValidos, true),
                fn (Builder $query): Builder => $query->where('estado', $this->estado),
            )
            ->when(
                filled($this->desde),
                fn (Builder $query): Builder => $query->whereDate('cancelado_at', '>=', $this->desde),
            )
            ->when(
                filled($this->hasta),
                fn (Builder $query): Builder => $query->whereDate('cancelado_at', '<=', $this->hasta),
            )
            ->when(
                $turnoBuscado !== '',
                fn (Builder $query): Builder => ctype_digit($turnoBuscado)
                    ? $query->where('turno_id', (int) $turnoBuscado)
                    : $query->whereRaw('1 = 0'),
            )
            ->orderByDesc('cancelado_at')
            ->orderByDesc('id')
            ->paginate($porPagina, ['*'], 'creditosPage');

        return $paginador->through(fn (Credito $credito): array => [
                'id' => $credito->id,
                'turno_id' => $credito->turno_id,
                'fecha' => $credito->cancelado_at?->format('d/m/Y H:i') ?? '-',
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
            ]);
    }

    /** @return array<string, string> */
    public function estadoOptions(): array
    {
        return [
            Credito::ESTADO_ESPERANDO_PAGO => 'Esperando pago',
            Credito::ESTADO_DISPONIBLE => 'Disponible',
            Credito::ESTADO_NO_APLICA => 'No corresponde',
        ];
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
