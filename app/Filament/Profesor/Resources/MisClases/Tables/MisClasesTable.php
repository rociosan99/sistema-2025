<?php

namespace App\Filament\Profesor\Resources\MisClases\Tables;

use App\Filament\Profesor\Resources\MisClases\MisClaseResource;
use App\Models\Turno;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MisClasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
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
                    ->state(fn (Turno $record): string => MisClaseResource::nombreCompleto($record->alumno)),

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
            ->recordActions([
                ViewAction::make()
                    ->label('Ver'),
            ])
            ->emptyStateHeading('Todavia no tenes clases finalizadas')
            ->emptyStateDescription('Cuando una clase confirmada finalice, va a aparecer en esta seccion.');
    }
}
