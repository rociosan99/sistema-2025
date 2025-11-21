<?php

namespace App\Filament\Resources\Programas\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

use App\Models\Institucion;
use App\Models\Carrera;
use App\Models\PlanEstudio;
use App\Models\Materia;
use App\Models\Tema;

class ProgramaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            /*
            |--------------------------------------------------------------------------
            | 1) INSTITUCIÓN (solo visible, no se guarda)
            |--------------------------------------------------------------------------
            */
            Forms\Components\Select::make('institucion_id')
                ->label('Institución')
                ->options(
                    Institucion::orderBy('institucion_nombre')
                        ->pluck('institucion_nombre', 'institucion_id')
                )
                ->default(fn($record) =>
                    $record?->plan?->carrera?->institucion?->institucion_id
                )
                ->reactive()
                ->required()
                ->dehydrated(false),

            /*
            |--------------------------------------------------------------------------
            | 2) CARRERA (solo visible, no se guarda)
            |--------------------------------------------------------------------------
            */
            Forms\Components\Select::make('carrera_id')
                ->label('Carrera')
                ->options(fn(Get $get, $record) =>
                    $get('institucion_id')
                        ? Carrera::where('carrera_institucion_id', $get('institucion_id'))
                            ->orderBy('carrera_nombre')
                            ->pluck('carrera_nombre', 'carrera_id')
                        : ($record
                            ? [$record->plan->carrera->carrera_id => $record->plan->carrera->carrera_nombre]
                            : [])
                )
                ->default(fn($record) => $record?->plan?->carrera?->carrera_id)
                ->reactive()
                ->required()
                ->dehydrated(false),

            /*
            |--------------------------------------------------------------------------
            | 3) PLAN DE ESTUDIO (este sí se guarda)
            |--------------------------------------------------------------------------
            */
            Forms\Components\Select::make('programa_plan_id')
                ->label('Plan de estudio')
                ->options(fn(Get $get, $record) =>
                    $get('carrera_id')
                        ? PlanEstudio::where('plan_carrera_id', $get('carrera_id'))
                            ->orderBy('plan_anio', 'desc')
                            ->pluck('plan_anio', 'plan_id')
                        : ($record
                            ? [$record->plan->plan_id => $record->plan->plan_anio]
                            : [])
                )
                ->default(fn($record) => $record?->programa_plan_id)
                ->reactive()
                ->required()
                ->dehydrated(true),

            /*
            |--------------------------------------------------------------------------
            | 4) MATERIA (se guarda y se debe precargar)
            |--------------------------------------------------------------------------
            */
            Forms\Components\Select::make('programa_materia_id')
                ->label('Materia')
                ->options(fn(Get $get, $record) =>
                    $get('programa_plan_id')
                        ? Materia::orderBy('materia_nombre')
                            ->pluck('materia_nombre', 'materia_id')
                        : ($record
                            ? [$record->materia->materia_id => $record->materia->materia_nombre]
                            : [])
                )
                ->default(fn($record) => $record?->programa_materia_id)
                ->searchable()
                ->required()
                ->dehydrated(true),

            /*
            |--------------------------------------------------------------------------
            | 5) AÑO DEL PROGRAMA
            |--------------------------------------------------------------------------
            */
            Forms\Components\TextInput::make('programa_anio')
                ->label('Año del programa')
                ->numeric()
                ->required()
                ->default(fn($record) => $record?->programa_anio),

            Forms\Components\Textarea::make('programa_descripcion')
                ->label('Descripción')
                ->rows(3)
                ->nullable()
                ->default(fn($record) => $record?->programa_descripcion)
                ->dehydrated(true)
                ->columnSpanFull(),

            /*
            |--------------------------------------------------------------------------
            | 6) TEMAS (checkbox list con hijos automáticos)
            |--------------------------------------------------------------------------
            */
            Forms\Components\Hidden::make('temas_prev')
                ->default([])
                ->dehydrated(false),

            Forms\Components\CheckboxList::make('temas')
                ->label('Temas')
                ->options(fn() => Tema::flattenTreeWithIndent())
                ->default(fn($record) =>
                    $record ? $record->temas->pluck('tema_id')->toArray() : []
                )
                ->reactive()
                ->allowHtml()
                ->columns(2)
                ->dehydrated(true)
                ->afterStateUpdated(function ($state, $set, $get) {
                    if (!is_array($state)) $state = $state ? [$state] : [];
                    $prev = is_array($get('temas_prev')) ? $get('temas_prev') : [];

                    $added = array_diff($state, $prev);

                    if (empty($added)) {
                        $set('temas_prev', $state);
                        return;
                    }

                    $newState = collect($state);

                    foreach ($added as $id) {
                        $desc = Tema::getDescendantIds((int)$id);
                        if (!empty($desc)) {
                            $newState = $newState->merge($desc);
                        }
                    }

                    $final = $newState->unique()->values()->all();

                    $set('temas', $final);
                    $set('temas_prev', $final);
                })
                ->columnSpanFull(),
        ]);
    }
}
