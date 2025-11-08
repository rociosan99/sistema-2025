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
                Tables\Columns\TextColumn::make('materia_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('materia_nombre')
                    ->label('Nombre de la materia')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('materia_anio')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('materia_descripcion')
                    ->label('Descripción')
                    ->limit(60),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // 🔽 Filtro por año
                Tables\Filters\SelectFilter::make('materia_anio')
                    ->label('Filtrar por año')
                    ->options(
                        fn () => \App\Models\Materia::query()
                            ->select('materia_anio')
                            ->distinct()
                            ->orderBy('materia_anio', 'desc')
                            ->pluck('materia_anio', 'materia_anio')
                    ),
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
