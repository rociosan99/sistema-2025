<?php

namespace App\Filament\Alumno\Resources\Turnos\Pages;

use App\Filament\Alumno\Resources\Turnos\TurnoResource;
use Filament\Resources\Pages\ListRecords;

class ListTurnos extends ListRecords
{
    protected static string $resource = TurnoResource::class;
}