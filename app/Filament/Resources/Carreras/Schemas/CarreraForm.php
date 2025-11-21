<?php

namespace App\Filament\Resources\Carreras\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class CarreraForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            // 🔹 Institución (N:1)
            Forms\Components\Select::make('carrera_institucion_id')
                ->label('Institución')
                ->relationship('institucion', 'institucion_nombre')
                ->searchable()
                ->required()
                ->placeholder('Selecciona la institución a la que pertenece'),

            // 🔹 Nombre de la carrera
            Forms\Components\TextInput::make('carrera_nombre')
                ->label('Nombre de la carrera')
                ->required()
                ->maxLength(150)
                ->placeholder('Ejemplo: Tecnicatura en Informática'),

            // 🔹 Descripción
            Forms\Components\Textarea::make('carrera_descripcion')
                ->label('Descripción')
                ->rows(3)
                ->nullable()
                ->columnSpanFull()
                ->placeholder('Breve descripción o detalles adicionales'),
        ]);
    }
}
