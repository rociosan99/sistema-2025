<?php

namespace App\Filament\Resources\Institucions\Pages;

use App\Filament\Resources\Institucions\InstitucionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstitucion extends CreateRecord
{
    protected static string $resource = InstitucionResource::class;

    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
