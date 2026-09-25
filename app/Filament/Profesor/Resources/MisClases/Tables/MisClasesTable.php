<?php

namespace App\Filament\Profesor\Resources\MisClases\Tables;

use App\Filament\Profesor\Resources\MisClases\MisClaseResource;
use App\Models\Materia;
use App\Models\Turno;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MisClasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->extraAttributes(['class' => 'mis-clases-profesor-table'])
            ->header(view('filament.profesor.resources.mis-clases.table-filters-styles'))
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(5)
            ->searchPlaceholder('Buscar por alumno o materia')
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('horario')
                    ->label('Horario')
                    ->state(fn (Turno $record): string => MisClaseResource::horario($record)),

                TextColumn::make('alumno')
                    ->label('Alumno')
                    ->state(fn (Turno $record): string => MisClaseResource::nombreCompleto($record->alumno))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('alumno', fn (Builder $query): Builder => $query
                            ->where(function (Builder $query) use ($search): void {
                                $query
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('apellido', 'like', "%{$search}%");
                            }))),

                TextColumn::make('materia.materia_nombre')
                    ->label('Materia')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('pago.estado')
                    ->label('Estado del pago')
                    ->badge()
                    ->color(fn (?string $state): string => MisClaseResource::pagoEstadoColor($state))
                    ->formatStateUsing(fn (?string $state): string => MisClaseResource::pagoEstadoLabel($state))
                    ->placeholder('-'),

                TextColumn::make('pago.monto')
                    ->label('Monto')
                    ->money(fn (Turno $record): string => $record->pago?->moneda ?? 'ARS', locale: 'es_AR')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('materia_id')
                    ->label('Materia')
                    ->options(fn (): array => Materia::query()
                        ->whereIn(
                            'materia_id',
                            MisClaseResource::getEloquentQuery()
                                ->select('materia_id')
                                ->distinct(),
                        )
                        ->orderBy('materia_nombre')
                        ->pluck('materia_nombre', 'materia_id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('alumno_id')
                    ->label('Alumno')
                    ->options(fn (): array => User::query()
                        ->whereIn(
                            'id',
                            MisClaseResource::getEloquentQuery()
                                ->select('alumno_id')
                                ->distinct(),
                        )
                        ->orderBy('name')
                        ->orderBy('apellido')
                        ->get(['id', 'name', 'apellido'])
                        ->mapWithKeys(fn (User $alumno): array => [
                            $alumno->id => MisClaseResource::nombreCompleto($alumno),
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('fecha_clase')
                    ->label('Fecha')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['desde'] ?? null,
                            fn (Builder $query, string $desde): Builder => $query
                                ->whereDate('fecha', '>=', $desde),
                        )
                        ->when(
                            $data['hasta'] ?? null,
                            fn (Builder $query, string $hasta): Builder => $query
                                ->whereDate('fecha', '<=', $hasta),
                        )),

                SelectFilter::make('calificacion')
                    ->label('Calificación')
                    ->options([
                        1 => '1 estrella',
                        2 => '2 estrellas',
                        3 => '3 estrellas',
                        4 => '4 estrellas',
                        5 => '5 estrellas',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['value'] ?? null,
                            fn (Builder $query, int|string $estrellas): Builder => $query
                                ->whereHas('calificacionProfesor', fn (Builder $query): Builder => $query
                                    ->where('estrellas', $estrellas)),
                        ))
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver'),
            ])
            ->emptyStateHeading('Todavia no tenes clases finalizadas')
            ->emptyStateDescription('Cuando una clase confirmada finalice, va a aparecer en esta seccion.');
    }
}
