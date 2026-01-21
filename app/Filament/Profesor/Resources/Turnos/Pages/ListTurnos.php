<?php

namespace App\Filament\Profesor\Resources\Turnos\Pages;

use App\Filament\Profesor\Resources\Turnos\TurnoResource;
use Filament\Resources\Pages\ListRecords;

class ListTurnos extends ListRecords
{
    protected static string $resource = TurnoResource::class;

    /**
     * ❌ Elimina el botón "Crear"
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
