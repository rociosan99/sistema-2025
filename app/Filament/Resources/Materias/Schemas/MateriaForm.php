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
                ->required()
                ->maxLength(150),

            Forms\Components\Textarea::make('materia_descripcion')
                ->label('Descripción')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

          Forms\Components\Select::make('materia_anio')
                ->label('Año de cursada')
                ->options([
                    1 => '1° año',
                    2 => '2° año',
                    3 => '3° año',
                    4 => '4° año',
                    5 => '5° año',
                    6 => '6° año',
                ])
                ->required()
                ->native(false),

        ]);
    }
}
