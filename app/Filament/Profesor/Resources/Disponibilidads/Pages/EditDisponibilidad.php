<?php

namespace App\Filament\Profesor\Resources\Disponibilidads\Pages;

use App\Filament\Profesor\Resources\Disponibilidads\DisponibilidadResource;
use App\Models\Disponibilidad;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditDisponibilidad extends EditRecord
{
    protected static string $resource = DisponibilidadResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['profesor_id'] = Auth::id();

        $this->validateNoOverlap(
            recordId: (int) $this->record->getKey(),
            profesorId: (int) $data['profesor_id'],
            diaSemana: (int) ($data['dia_semana'] ?? 0),
            horaInicio: (string) ($data['hora_inicio'] ?? ''),
            horaFin: (string) ($data['hora_fin'] ?? ''),
        );

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    private function validateNoOverlap(int $recordId, int $profesorId, int $diaSemana, string $horaInicio, string $horaFin): void
    {
        if (!$profesorId || !$diaSemana || !$horaInicio || !$horaFin) {
            return;
        }

        $overlapExists = Disponibilidad::query()
            ->where('profesor_id', $profesorId)
            ->where('dia_semana', $diaSemana)
            ->whereKeyNot($recordId)                   // ignora el registro actual
            ->whereTime('hora_inicio', '<', $horaFin)
            ->whereTime('hora_fin', '>', $horaInicio)
            ->exists();

        if ($overlapExists) {
            $this->addError('hora_inicio', 'Ese horario se superpone con otra disponibilidad existente.');
            $this->addError('hora_fin', 'Elegí un rango que no se cruce.');

            Notification::make()
                ->title('No se pudo guardar')
                ->body('La edición genera un solapamiento con otra disponibilidad del mismo día.')
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
