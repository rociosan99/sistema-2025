<?php

namespace App\Filament\Resources\Materias\Pages;

use App\Filament\Resources\Materias\MateriaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMateria extends CreateRecord
{
    protected static string $resource = MateriaResource::class;

    protected function afterCreate(): void
    {
        $temas = $this->data['temas'] ?? [];
        $this->record->temas()->sync($temas);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
