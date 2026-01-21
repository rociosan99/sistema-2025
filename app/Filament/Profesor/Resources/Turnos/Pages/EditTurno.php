<?php

namespace App\Filament\Profesor\Resources\Turnos\Pages;

use App\Filament\Profesor\Resources\Turnos\TurnoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTurno extends EditRecord
{
    protected static string $resource = TurnoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
