<?php

namespace App\Filament\Resources\Materias\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class MateriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('materia_nombre')
                ->label('Nombre de la materia')
                ->placeholder('Ejemplo: Matemática Discreta')
                ->required()
                ->maxLength(150)
                ->unique(ignoreRecord: true),

            Forms\Components\Textarea::make('materia_descripcion')
                ->label('Descripción')
                ->placeholder('Descripción breve de la materia (opcional)')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

            // Campo para año
            Forms\Components\TextInput::make('materia_anio')
                ->label('Año')
                ->numeric()
                ->required()
                ->minValue(1900)
                ->maxValue(now()->year) // 🔸 usa el año actual automáticamente
                ->rules([
                    'integer',
                    'min:1900',
                    'max:' . now()->year,
                ])
                ->placeholder(now()->year)
                ->helperText('Ingrese un año entre 1900 y ' . now()->year)
                ->validationMessages([
                    'max' => 'El año no puede ser mayor al actual (' . now()->year . ').',
                    'min' => 'El año no puede ser menor a 1900.',
                    'integer' => 'Debe ingresar un año válido.',
                ]),
        ]); //cerramos correctamente el array y el método schema()
    }
}
