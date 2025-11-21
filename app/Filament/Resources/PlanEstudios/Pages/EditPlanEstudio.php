<?php

namespace App\Filament\Resources\PlanEstudios\Pages;

use App\Filament\Resources\PlanEstudios\PlanEstudioResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlanEstudio extends EditRecord
{
    protected static string $resource = PlanEstudioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
