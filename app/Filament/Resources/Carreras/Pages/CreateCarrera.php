<?php

namespace App\Filament\Resources\Carreras\Pages;

use App\Filament\Resources\Carreras\CarreraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCarrera extends CreateRecord
{
    protected static string $resource = CarreraResource::class;

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
