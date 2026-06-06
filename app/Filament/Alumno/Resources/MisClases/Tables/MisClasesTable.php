<?php

namespace App\Filament\Alumno\Resources\MisClases\Tables;

use App\Filament\Alumno\Resources\MisClases\MisClaseResource;
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

                TextColumn::make('materia.materia_nombre')
                    ->label('Materia')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tema.tema_nombre')
                    ->label('Tema')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('profesor')
                    ->label('Profesor')
                    ->state(fn (Turno $record): string => MisClaseResource::nombreCompleto($record->profesor)),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => MisClaseResource::estadoColor($state))
                    ->formatStateUsing(fn (?string $state): string => MisClaseResource::estadoLabel($state)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver'),
            ])
            ->emptyStateHeading('Todavia no tenes clases finalizadas')
            ->emptyStateDescription('Cuando una clase confirmada finalice, va a aparecer en esta seccion.');
    }
}
