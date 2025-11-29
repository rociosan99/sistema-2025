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
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['profesor_id'] = Auth::id();   // ← SIEMPRE asigna el profesor correcto
        return $data;
    }

    /**
     * Redirigir siempre al index después de crear
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
