<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Texto correcto del botón
     */
    protected function getCreateFormActionLabel(): string
    {
        return 'Crear usuario';
    }

    /**
     * Redirección al listado luego de crear
     */
    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
