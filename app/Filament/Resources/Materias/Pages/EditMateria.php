<?php

namespace App\Filament\Resources\Materias\Pages;

use App\Filament\Resources\Materias\MateriaResource;
use Filament\Resources\Pages\EditRecord;

class EditMateria extends EditRecord
{
    protected static string $resource = MateriaResource::class;

    // ⭐ Redirigir al index después de editar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // ⭐ Guardar relación con temas
    protected function afterSave(): void
    {
        $temas = $this->data['temas'] ?? [];
        $this->record->temas()->sync($temas);
    }
}
