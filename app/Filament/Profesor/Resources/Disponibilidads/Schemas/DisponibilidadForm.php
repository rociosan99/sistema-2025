<?php

namespace App\Filament\Profesor\Resources\Disponibilidads\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Closure;

class DisponibilidadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Select::make('dia_semana')
                ->label('Día de la semana')
                ->required()
                ->options([
                    1 => 'Lunes',
                    2 => 'Martes',
                    3 => 'Miércoles',
                    4 => 'Jueves',
                    5 => 'Viernes',
                    6 => 'Sábado',
                    7 => 'Domingo',
                ]),

            TimePicker::make('hora_inicio')
                ->label('Desde')
                ->seconds(false)
                ->required(),

            TimePicker::make('hora_fin')
                ->label('Hasta')
                ->seconds(false)
                ->required()
                ->rule(function (Get $get) {
                    return function (string $attribute, $value, Closure $fail) use ($get) {
                        $inicio = $get('hora_inicio');
                        $fin = $value;

                        if ($inicio && $fin && $inicio >= $fin) {
                            $fail('La hora de fin debe ser mayor que la hora de inicio.');
                        }
                    };
                }),

        ]);
    }
}


