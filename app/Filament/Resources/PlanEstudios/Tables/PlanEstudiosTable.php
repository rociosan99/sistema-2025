<?php

namespace App\Filament\Resources\PlanEstudios\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class PlanEstudiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('plan_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('carrera.carrera_nombre')
                    ->label('Carrera')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('plan_anio')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan_descripcion')
                    ->label('Descripción')
                    ->limit(60),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
