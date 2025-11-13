<?php

namespace App\Filament\Resources\Materias\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use App\Models\Tema;

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
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('materia_anio')
                ->label('Año')
                ->numeric()
                ->required()
                ->minValue(1900)
                ->maxValue(now()->year),

            // ⭐ Temas con jerarquía + colores + guardado correcto
            Forms\Components\CheckboxList::make('temas')
                ->label('Temas asociados')
                ->options(fn () => Tema::flattenTreeWithIndent())
                ->allowHtml()
                ->columns(2)
                ->searchable()
                ->dehydrateStateUsing(fn ($state) => $state)
                ->statePath('temas')
                ->columnSpanFull(),
        ]);
    }
}
