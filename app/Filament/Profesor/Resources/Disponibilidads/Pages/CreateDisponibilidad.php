<?php

namespace App\Filament\Profesor\Resources\Disponibilidads\Pages;

use App\Filament\Profesor\Resources\Disponibilidads\DisponibilidadResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDisponibilidad extends CreateRecord
{
    protected static string $resource = DisponibilidadResource::class;

    /**
     * Antes de guardar, agregamos automáticamente el profesor_id
     * para que SIEMPRE se asocie la disponibilidad
     * al profesor que está logueado.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['profesor_id'] = Auth::id();
        return $data;
    }

    /**
     * Después de crear, volver siempre al listado.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
