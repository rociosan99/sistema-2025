<?php

namespace App\Filament\Profesor\Resources\Disponibilidads\Pages;

use App\Filament\Profesor\Resources\Disponibilidads\DisponibilidadResource;
use App\Models\Disponibilidad;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDisponibilidad extends CreateRecord
{
    protected static string $resource = DisponibilidadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['profesor_id'] = Auth::id();

        // Validación de solapamiento (si falla, muestra aviso y frena el guardado)
        $this->validateNoOverlap(
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

    /**
     * Bloquea si hay solapamiento:
     * new_start < existing_end AND new_end > existing_start
     * (permite 16-19 y 19-21)
     */
    private function validateNoOverlap(int $profesorId, int $diaSemana, string $horaInicio, string $horaFin): void
    {
        if (!$profesorId || !$diaSemana || !$horaInicio || !$horaFin) {
            return;
        }

        $overlapExists = Disponibilidad::query()
            ->where('profesor_id', $profesorId)
            ->where('dia_semana', $diaSemana)
            ->whereTime('hora_inicio', '<', $horaFin)   // existing_start < new_end
            ->whereTime('hora_fin', '>', $horaInicio)   // existing_end > new_start
            ->exists();

        if ($overlapExists) {
            // 1) Error abajo del campo
            $this->addError('hora_inicio', 'Ese horario se superpone con una disponibilidad existente.');
            $this->addError('hora_fin', 'Elegí un rango que no se cruce.');

            // 2) Notificación visible
            Notification::make()
                ->title('No se pudo guardar')
                ->body('Ya existe una disponibilidad para ese día que se superpone con el horario ingresado.')
                ->danger()
                ->send();

            // 3) Frenar el guardado
            $this->halt();
        }
    }
}
