<?php

namespace App\Filament\Alumno\Resources\Turnos\Pages;

use App\Filament\Alumno\Resources\Turnos\TurnoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListTurnos extends ListRecords
{
    protected static string $resource = TurnoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('solicitarTurno')
                ->label('Solicitar turno')
                ->icon('heroicon-o-plus')
                ->url(fn () => route('filament.alumno.pages.solicitar-turno'))
                ->color('primary'),
        ];
    }
}
