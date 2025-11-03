<?php

namespace App\Filament\Resources\Temas\Pages;

use App\Filament\Resources\Temas\TemaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTema extends CreateRecord
{
    protected static string $resource = TemaResource::class;

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
