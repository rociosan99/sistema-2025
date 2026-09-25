<?php

namespace App\Filament\Resources\Pagos\Tables;

use App\Filament\Resources\Pagos\PagoResource;
use App\Models\Materia;
use App\Models\Pago;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->extraAttributes(['class' => 'pagos-admin-table'])
            ->header(view('filament.admin.resources.pagos.table-filters-styles'))
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->searchPlaceholder('Buscar por alumno, profesor o materia')
            ->columns([
                TextColumn::make('pago_id')
                    ->label('Pago ID')
                    ->sortable(),

                TextColumn::make('alumno')
                    ->label('Alumno')
                    ->state(fn (Pago $record): string => PagoResource::nombreCompleto($record->turno?->alumno))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('turno.alumno', fn (Builder $query): Builder => $query
                            ->where(function (Builder $query) use ($search): void {
                                $query
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('apellido', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            }))),

                TextColumn::make('profesor')
                    ->label('Profesor')
                    ->state(fn (Pago $record): string => PagoResource::nombreCompleto($record->turno?->profesor))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('turno.profesor', fn (Builder $query): Builder => $query
                            ->where(function (Builder $query) use ($search): void {
                                $query
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('apellido', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            }))),

                TextColumn::make('turno.materia.materia_nombre')
                    ->label('Materia')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('turno.fecha')
                    ->label('Fecha de clase')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('horario')
                    ->label('Horario')
                    ->state(fn (Pago $record): string => PagoResource::horario($record))
                    ->placeholder('-'),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->money(fn (Pago $record): string => $record->moneda ?? 'ARS', locale: 'es_AR')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => PagoResource::estadoColor($state))
                    ->formatStateUsing(fn (?string $state): string => PagoResource::estadoLabel($state))
                    ->sortable(),

                TextColumn::make('fecha_aprobado')
                    ->label('Fecha de aprobacion')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(PagoResource::estadoOptions())
                    ->native(false),

                SelectFilter::make('materia_id')
                    ->label('Materia')
                    ->options(fn (): array => Materia::query()
                        ->orderBy('materia_nombre')
                        ->pluck('materia_nombre', 'materia_id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['value'] ?? null,
                            fn (Builder $query, int|string $materiaId): Builder => $query
                                ->whereHas('turno', fn (Builder $query): Builder => $query
                                    ->where('materia_id', $materiaId)),
                        ))
                    ->searchable()
                    ->preload()
                    ->native(false),

                Filter::make('fecha_clase')
                    ->label('Fecha de clase')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->whereHas('turno', fn (Builder $query): Builder => $query
                            ->when(
                                $data['desde'] ?? null,
                                fn (Builder $query, string $desde): Builder => $query
                                    ->whereDate('fecha', '>=', $desde),
                            )
                            ->when(
                                $data['hasta'] ?? null,
                                fn (Builder $query, string $hasta): Builder => $query
                                    ->whereDate('fecha', '<=', $hasta),
                            ))),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver'),
            ]);
    }
}
