<?php

namespace App\Filament\Profesor\Resources\Disponibilidads\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

// Acciones correctas Filament 4
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

class DisponibilidadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dia_semana')
                    ->label('Día')
                    ->formatStateUsing(fn ($state) => [
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                        7 => 'Domingo',
                    ][$state]),

                TextColumn::make('hora_inicio')
                    ->label('Inicio'),

                TextColumn::make('hora_fin')
                    ->label('Fin'),
            ])

            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])

            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
