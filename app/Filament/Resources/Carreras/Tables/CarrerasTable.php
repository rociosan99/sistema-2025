<?php

namespace App\Filament\Resources\Carreras\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class CarrerasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('carrera_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('institucion.institucion_nombre')
                    ->label('Institución')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('carrera_nombre')
                    ->label('Nombre de la carrera')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('materias_count')
                    ->label('Materias')
                    ->counts('materias')
                    ->sortable(),

                Tables\Columns\TextColumn::make('carrera_descripcion')
                    ->label('Descripción')
                    ->limit(60),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\EditAction::make()->label('Editar'),
                Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->toolbarActions([
                Actions\ActionGroup::make([
                    Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
