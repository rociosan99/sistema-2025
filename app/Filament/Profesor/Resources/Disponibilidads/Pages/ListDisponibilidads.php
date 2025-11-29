<?php

namespace App\Filament\Profesor\Resources\Disponibilidads\Pages;

use App\Filament\Profesor\Resources\Disponibilidads\DisponibilidadResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;

class ListDisponibilidads extends ListRecords
{
    protected static string $resource = DisponibilidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
