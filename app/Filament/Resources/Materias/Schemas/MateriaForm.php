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
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\TextInput::make('materia_anio')
                ->label('Año')
                ->numeric()
                ->required()
                ->minValue(1900)
                ->maxValue(now()->year),

            // 🔹 Campo oculto para guardar el estado anterior
            Forms\Components\Hidden::make('temas_prev')
                ->default([])
                ->dehydrated(false),

            // 🔹 CheckboxList con selección automática de hijos
            Forms\Components\CheckboxList::make('temas')
                ->label('Temas asociados (padre → hijos automáticos)')
                ->options(fn () => Tema::flattenTreeWithIndent())
                ->allowHtml()
                ->columns(2)
                ->searchable()
                ->statePath('temas')
                ->dehydrateStateUsing(fn ($state) => $state)
                ->reactive()
                ->afterStateUpdated(function ($state, $set, $get) {

                    // estado actual (nuevo)
                    if (!is_array($state)) {
                        $state = $state ? [$state] : [];
                    }

                    // estado anterior (antes del cambio)
                    $prev = $get('temas_prev') ?? [];
                    if (!is_array($prev)) {
                        $prev = $prev ? [$prev] : [];
                    }

                    // IDs que se AGREGARON (no los que se quitaron)
                    $added = array_diff($state, $prev);

                    // si no se agregó nada (es decir, el usuario desmarcó cosas), no hacemos nada
                    if (count($added) === 0) {
                        // actualizamos el estado previo y terminamos
                        $set('temas_prev', $state);
                        return;
                    }

                    // Por cada tema agregado, si es padre, agregamos sus descendientes
                    $newState = collect($state);

                    foreach ($added as $idAgregado) {
                        $descendants = Tema::getDescendantIds((int)$idAgregado);

                        if (!empty($descendants)) {
                            $newState = $newState->merge($descendants);
                        }
                    }

                    // quitamos duplicados y seteamos el estado final
                    $final = $newState->unique()->values()->all();

                    $set('temas', $final);
                    $set('temas_prev', $final);
                })
                ->columnSpanFull(),
        ]);
    }
}
