<?php

namespace App\Filament\Resources\PlanEstudios\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class PlanEstudioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Forms\Components\Select::make('plan_carrera_id')
                ->label('Carrera')
                ->relationship('carrera', 'carrera_nombre')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('plan_anio')
                ->label('Año')
                ->numeric()
                ->required()
                ->minValue(1900)
                ->maxValue(now()->year + 5),

            Forms\Components\Textarea::make('plan_descripcion')
                ->label('Descripción')
                ->nullable()
                ->columnSpanFull(),
        ]);
    }
}
