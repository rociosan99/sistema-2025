<?php

namespace App\Filament\Resources\PlanEstudios\Pages;

use App\Filament\Resources\PlanEstudios\PlanEstudioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlanEstudios extends ListRecords
{
    protected static string $resource = PlanEstudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
