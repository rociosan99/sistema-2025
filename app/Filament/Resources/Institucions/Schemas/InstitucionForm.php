<?php

namespace App\Filament\Resources\Institucions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;

class InstitucionForm
{
     public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('institucion_nombre')
                ->label('Nombre de la institución')
                ->required()
                ->maxLength(150)
                ->placeholder('Ejemplo: Instituto Cambridge o Escuela San Martín'),

            Forms\Components\Textarea::make('institucion_descripcion')
                ->label('Descripción')
                ->rows(3)
                ->placeholder('Descripción breve de la institución (opcional)')
                ->columnSpanFull()
                ->nullable(),
        ]);
    }
}
