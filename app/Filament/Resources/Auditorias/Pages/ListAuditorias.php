<?php

namespace App\Filament\Resources\Auditorias\Pages;

use App\Filament\Resources\Auditorias\AuditoriaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAuditorias extends ListRecords
{
    protected static string $resource = AuditoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
