<?php

namespace App\Filament\Resources\Temas\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class TemasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tema_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tema_nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.tema_nombre')
                    ->label('Tema padre')
                    ->badge()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('tema_descripcion')
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
