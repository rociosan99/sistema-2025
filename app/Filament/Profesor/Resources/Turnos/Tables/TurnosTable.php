<?php

namespace App\Filament\Profesor\Resources\Turnos\Tables;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class TurnosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('alumno.name')
                    ->label('Alumno')
                    ->searchable(),

                // OJO: si tu campo real es materia_nombre, cambiá a:
                // TextColumn::make('materia.materia_nombre')
                TextColumn::make('materia.nombre')
                    ->label('Materia'),

                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date(),

                TextColumn::make('hora_inicio')
                    ->label('Desde'),

                TextColumn::make('hora_fin')
                    ->label('Hasta'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'confirmado',
                        'danger'  => 'rechazado',
                        'gray'    => 'vencido',
                    ])
                    // Opcional: mostrar "vencido" aunque en BD esté "pendiente"
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === 'pendiente' && self::estaVencido($record)) {
                            return 'Vencido';
                        }

                        return ucfirst((string) $state);
                    }),
            ])
            ->recordActions([
                Action::make('confirmar')
                    ->label('Confirmar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        $record->estado === 'pendiente' &&
                        ! self::estaVencido($record)
                    )
                    ->action(function ($record) {
                        // seguridad extra: aunque alguien fuerce el click
                        if (self::estaVencido($record)) {
                            return;
                        }

                        $record->update(['estado' => 'confirmado']);
                    }),

                Action::make('rechazar')
                    ->label('Rechazar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        $record->estado === 'pendiente' &&
                        ! self::estaVencido($record)
                    )
                    ->action(function ($record) {
                        if (self::estaVencido($record)) {
                            return;
                        }

                        $record->update(['estado' => 'rechazado']);
                    }),
            ])
            ->paginated();
    }

    /**
     * ⛔ Determina si el turno está vencido (según fecha + hora_fin)
     */
    protected static function estaVencido($turno): bool
    {
        // 1) Fecha base (puede venir como Carbon por el cast 'date')
        $fecha = $turno->fecha instanceof CarbonInterface
            ? $turno->fecha->copy()
            : Carbon::parse($turno->fecha);

        // 2) Hora fin puede venir:
        // - como Carbon (por tu cast datetime:H:i)
        // - como string 'HH:MM:SS'
        // - como string 'YYYY-MM-DD HH:MM:SS'
        $horaFin = $turno->hora_fin;

        if ($horaFin instanceof CarbonInterface) {
            $horaFinStr = $horaFin->format('H:i:s');
        } else {
            $horaFinStr = (string) $horaFin;

            // Si viene con fecha incluida, extraer solo hora
            if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaFinStr)) {
                $horaFinStr = Carbon::parse($horaFinStr)->format('H:i:s');
            }
        }

        // 3) Armar datetime final de fin de turno
        $finTurno = $fecha->copy()->setTimeFromTimeString($horaFinStr);

        return $finTurno->isPast();
    }
}
