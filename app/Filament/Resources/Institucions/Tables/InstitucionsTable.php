<?php

namespace App\Filament\Resources\Institucions\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class InstitucionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('institucion_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('institucion_nombre')
                    ->label('Nombre de la institución')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('institucion_descripcion')
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
            // 🔽 Nueva forma recomendada en Filament 4.1+
            ->toolbarActions([
                Actions\ActionGroup::make([
                    Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
