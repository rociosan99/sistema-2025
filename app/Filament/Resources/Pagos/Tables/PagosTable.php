<?php

namespace App\Filament\Resources\Pagos\Tables;

use App\Filament\Resources\Pagos\PagoResource;
use App\Models\Pago;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('pago_id')
                    ->label('Pago ID')
                    ->sortable(),

                TextColumn::make('alumno')
                    ->label('Alumno')
                    ->state(fn (Pago $record): string => PagoResource::nombreCompleto($record->turno?->alumno)),

                TextColumn::make('profesor')
                    ->label('Profesor')
                    ->state(fn (Pago $record): string => PagoResource::nombreCompleto($record->turno?->profesor)),

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
                    ->options(PagoResource::estadoOptions()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver'),
            ]);
    }
}
