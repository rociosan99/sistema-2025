<?php

namespace App\Filament\Resources\Programas\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class ProgramasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('plan.carrera.institucion.institucion_nombre')
                    ->label('Institución')
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('plan.carrera.carrera_nombre')
                    ->label('Carrera')
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('plan.plan_anio')
                    ->label('Plan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('materia.materia_nombre')
                    ->label('Materia')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('programa_anio')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('temas_list')
                    ->label('Temas')
                    ->getStateUsing(fn ($record) =>
                        $record->temas->pluck('tema_nombre')->join(', ')
                    )
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
