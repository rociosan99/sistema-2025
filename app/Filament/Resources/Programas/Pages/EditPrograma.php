<?php

namespace App\Filament\Resources\Programas\Pages;

use App\Filament\Resources\Programas\ProgramaResource;
use Filament\Resources\Pages\EditRecord;

class EditPrograma extends EditRecord
{
    protected static string $resource = ProgramaResource::class;

    /**
     * 🔥 CARGA los valores reales del registro
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $programa = $this->record;

        $data['institucion_id'] = $programa->plan->carrera->institucion->institucion_id;
        $data['carrera_id']     = $programa->plan->carrera->carrera_id;
        $data['programa_plan_id'] = $programa->plan->plan_id;
        $data['programa_materia_id'] = $programa->materia->materia_id;

        // 🔥 Temas seleccionados
        $data['temas'] = $programa->temas->pluck('tema_id')->toArray();
        $data['temas_prev'] = $data['temas'];

        return $data;
    }

    /**
     * 🔥 GUARDA los temas en la tabla pivot
     */
    protected function afterSave(): void
    {
        $temas = $this->data['temas'] ?? [];
        $this->record->temas()->sync($temas);
    }

    /**
     * 🔥 Redirige al índice después de editar
     */
    protected function getRedirectUrl(): string
    {
        return ProgramaResource::getUrl('index');
    }
}
