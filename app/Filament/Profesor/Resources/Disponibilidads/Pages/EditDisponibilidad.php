<?php

namespace App\Filament\Profesor\Resources\Disponibilidads\Pages;

use App\Filament\Profesor\Resources\Disponibilidads\DisponibilidadResource;
use Filament\Resources\Pages\EditRecord;

class EditDisponibilidad extends EditRecord
{
    protected static string $resource = DisponibilidadResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
