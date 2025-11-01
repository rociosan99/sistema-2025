<?php

namespace App\Filament\Resources\Materias\Pages;

use App\Filament\Resources\Materias\MateriaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterias extends ListRecords
{
    protected static string $resource = MateriaResource::class;

        protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Nueva Materia') // cambia el texto del botón
                ->icon('heroicon-o-plus'), // opcional: agrega ícono de suma
        ];
    }

}
