<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Texto del botón de guardar
     */
    protected function getSaveFormActionLabel(): string
    {
        return 'Guardar cambios';
    }

    /**
     * Redirección al listado luego de editar
     */
    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
