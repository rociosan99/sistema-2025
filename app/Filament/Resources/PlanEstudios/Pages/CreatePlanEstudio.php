<?php

namespace App\Filament\Resources\PlanEstudios\Pages;

use App\Filament\Resources\PlanEstudios\PlanEstudioResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePlanEstudio extends CreateRecord
{
    protected static string $resource = PlanEstudioResource::class;

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('index');
    }
}
