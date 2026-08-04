<?php

namespace App\Filament\Profesor\Resources\Turnos\Pages;

use App\Filament\Profesor\Resources\Turnos\TurnoResource;
use App\Services\TurnoRespuestaProfesorService;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTurno extends ViewRecord
{
    protected static string $resource = TurnoResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        abort_unless((int) $this->record->profesor_id === (int) Auth::id(), 404);

        app(TurnoRespuestaProfesorService::class)
            ->marcarComoVencidoSiCorresponde($this->record, (int) Auth::id());
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
