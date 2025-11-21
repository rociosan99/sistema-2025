<?php

namespace App\Filament\Resources\Materias\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class MateriasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('materia_nombre')
                    ->label('Materia')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('materia_anio')
                    ->label('Año')
                    ->sortable(),

             
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
