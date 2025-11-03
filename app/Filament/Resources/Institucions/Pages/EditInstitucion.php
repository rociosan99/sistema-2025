<?php

namespace App\Filament\Resources\Institucions\Pages;

use App\Filament\Resources\Institucions\InstitucionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstitucion extends EditRecord
{
    protected static string $resource = InstitucionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
