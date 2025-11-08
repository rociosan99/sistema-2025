<?php

namespace App\Filament\Resources\Carreras\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use App\Models\Materia;

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

            // 🔹 Materias (N:M) — MULTISELECT con AUTOCOMPLETE
            Forms\Components\Select::make('materias')
                ->label('Materias de la carrera')
                ->relationship('materias', 'materia_nombre') // usa belongsToMany
                ->multiple()
                ->preload(false)      // no cargar todo; ideal si hay muchas
                ->searchable()        // activa el input para escribir y buscar
                // (Opcional) resultados de búsqueda personalizados:
                ->getSearchResultsUsing(function (string $search) {
                    return Materia::query()
                        ->where('materia_nombre', 'like', "%{$search}%")
                        ->orWhere('materia_anio', 'like', "%{$search}%")
                        ->orderBy('materia_anio', 'desc')
                        ->limit(50)
                        ->pluck('materia_nombre', 'materia_id');
                })
                // (Opcional) cómo mostrar el label ya seleccionado:
                ->getOptionLabelUsing(function ($value): ?string {
                    $m = Materia::find($value);
                    return $m ? "{$m->materia_nombre} ({$m->materia_anio})" : null;
                })
                ->helperText('Escribí para buscar y seleccionar múltiples materias (ej: “Mate” → Matemática 1, Matemática 2).')
                ->columnSpanFull(),
        ]);
    }
}
