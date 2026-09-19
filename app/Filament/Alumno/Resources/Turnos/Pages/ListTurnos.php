<?php

namespace App\Filament\Alumno\Resources\Turnos\Pages;

use App\Filament\Alumno\Resources\Turnos\TurnoResource;
use App\Models\Turno;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListTurnos extends ListRecords
{
    protected static string $resource = TurnoResource::class;

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.alumno.resources.turnos.pages.list-turnos-tabs-styles'),
                $this->getTabsContentComponent()
                    ->extraAttributes(['class' => 'turnos-alumno-tabs']),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    public function getTabs(): array
    {
        $fechaActual = now()->toDateString();
        $horaActual = now()->format('H:i:s');

        return [
            'todos' => Tab::make('Todos')
                ->badge($this->contarTurnos()),

            'esperando_respuesta' => Tab::make('Esperando respuesta')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->aplicarEsperandoRespuesta(
                    $query,
                    $fechaActual,
                    $horaActual,
                ))
                ->badge($this->contarTurnos(fn (Builder $query): Builder => $this->aplicarEsperandoRespuesta(
                    $query,
                    $fechaActual,
                    $horaActual,
                ))),

            'pendientes_pago' => Tab::make('Pendientes de pago')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->aplicarPendientesPago(
                    $query,
                    $fechaActual,
                    $horaActual,
                ))
                ->badge($this->contarTurnos(fn (Builder $query): Builder => $this->aplicarPendientesPago(
                    $query,
                    $fechaActual,
                    $horaActual,
                ))),

            'proximos' => Tab::make('Próximos')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->aplicarProximos(
                    $query,
                    $fechaActual,
                    $horaActual,
                ))
                ->badge($this->contarTurnos(fn (Builder $query): Builder => $this->aplicarProximos(
                    $query,
                    $fechaActual,
                    $horaActual,
                ))),

            'suspendidos' => Tab::make('Suspendidos')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->aplicarSuspendidos($query))
                ->badge($this->contarTurnos(fn (Builder $query): Builder => $this->aplicarSuspendidos($query))),

            'historial' => Tab::make('Historial')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->aplicarHistorial($query))
                ->badge($this->contarTurnos(fn (Builder $query): Builder => $this->aplicarHistorial($query))),
        ];
    }

    private function aplicarEsperandoRespuesta(
        Builder $query,
        string $fechaActual,
        string $horaActual,
    ): Builder {
        return $this->aplicarNoComenzados(
            $query->where('estado', Turno::ESTADO_PENDIENTE),
            $fechaActual,
            $horaActual,
        );
    }

    private function aplicarPendientesPago(
        Builder $query,
        string $fechaActual,
        string $horaActual,
    ): Builder {
        return $this->aplicarNoComenzados(
            $query->where('estado', Turno::ESTADO_PENDIENTE_PAGO),
            $fechaActual,
            $horaActual,
        );
    }

    private function aplicarProximos(
        Builder $query,
        string $fechaActual,
        string $horaActual,
    ): Builder {
        return $this->aplicarNoFinalizados(
            $query->where('estado', Turno::ESTADO_CONFIRMADO),
            $fechaActual,
            $horaActual,
        );
    }

    private function aplicarSuspendidos(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('estado', Turno::ESTADO_SUSPENDIDO_PROFESOR)
                ->orWhere(function (Builder $query): void {
                    $query
                        ->where('estado', Turno::ESTADO_CANCELADO)
                        ->whereIn('cancelacion_tipo', ['sin_cargo', 'con_cargo'])
                        ->whereNotNull('cancelado_at')
                        ->whereNull('reprogramado_por_turno_id');
                });
        });
    }

    private function aplicarHistorial(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereIn('estado', [Turno::ESTADO_VENCIDO, Turno::ESTADO_RECHAZADO])
                ->orWhere(function (Builder $query): void {
                    $query
                        ->where('estado', Turno::ESTADO_CANCELADO)
                        ->where(function (Builder $query): void {
                            $query
                                ->whereNotNull('reprogramado_por_turno_id')
                                ->orWhereNull('cancelado_at')
                                ->orWhereNull('cancelacion_tipo')
                                ->orWhereNotIn('cancelacion_tipo', ['sin_cargo', 'con_cargo']);
                        });
                });
        });
    }

    private function aplicarNoComenzados(
        Builder $query,
        string $fechaActual,
        string $horaActual,
    ): Builder {
        return $query->where(function (Builder $query) use ($fechaActual, $horaActual): void {
            $query
                ->whereDate('fecha', '>', $fechaActual)
                ->orWhere(function (Builder $query) use ($fechaActual, $horaActual): void {
                    $query
                        ->whereDate('fecha', $fechaActual)
                        ->whereTime('hora_inicio', '>', $horaActual);
                });
        });
    }

    private function aplicarNoFinalizados(
        Builder $query,
        string $fechaActual,
        string $horaActual,
    ): Builder {
        return $query->where(function (Builder $query) use ($fechaActual, $horaActual): void {
            $query
                ->whereDate('fecha', '>', $fechaActual)
                ->orWhere(function (Builder $query) use ($fechaActual, $horaActual): void {
                    $query
                        ->whereDate('fecha', $fechaActual)
                        ->whereTime('hora_fin', '>', $horaActual);
                });
        });
    }

    private function contarTurnos(?callable $aplicarCondicion = null): int
    {
        $query = TurnoResource::getEloquentQuery();

        if ($aplicarCondicion !== null) {
            $aplicarCondicion($query);
        }

        return $query->count();
    }
}
