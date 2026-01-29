<?php

namespace App\Filament\Profesor\Resources\Turnos\Tables;

use App\Models\Turno;
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

                TextColumn::make('materia.materia_nombre')
                    ->label('Materia')
                    ->placeholder('-'),

                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date(),

                TextColumn::make('hora_inicio')
                    ->label('Desde')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),

                TextColumn::make('hora_fin')
                    ->label('Hasta')
                    ->formatStateUsing(fn ($state) => substr((string) $state, 0, 5)),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => Turno::ESTADO_PENDIENTE,
                        'info'    => Turno::ESTADO_ACEPTADO,
                        'primary' => Turno::ESTADO_PENDIENTE_PAGO,
                        'success' => Turno::ESTADO_CONFIRMADO, // pago OK
                        'danger'  => Turno::ESTADO_RECHAZADO,
                        'gray'    => Turno::ESTADO_VENCIDO,
                    ])
                    ->formatStateUsing(function ($state, $record) {
                        if ($state === Turno::ESTADO_PENDIENTE && self::estaVencido($record)) {
                            return 'Vencido';
                        }

                        // Etiquetas amigables
                        return match ($state) {
                            Turno::ESTADO_PENDIENTE => 'Pendiente',
                            Turno::ESTADO_ACEPTADO => 'Aceptado (pendiente de pago)',
                            Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
                            Turno::ESTADO_CONFIRMADO => 'Clase Pagada',
                            Turno::ESTADO_RECHAZADO => 'Rechazado',
                            Turno::ESTADO_CANCELADO => 'Cancelado',
                            Turno::ESTADO_VENCIDO => 'Vencido',
                            default => ucfirst((string) $state),
                        };
                    }),
            ])
            ->recordActions([
                Action::make('confirmar')
                    ->label('Aceptar')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        $record->estado === Turno::ESTADO_PENDIENTE &&
                        ! self::estaVencido($record)
                    )
                    ->action(function ($record) {
                        if (self::estaVencido($record)) {
                            return;
                        }

                        // IMPORTANTE: el profe NO confirma pago.
                        // Solo acepta la solicitud.
                        $record->update(['estado' => Turno::ESTADO_ACEPTADO]);
                    }),

                Action::make('rechazar')
                    ->label('Rechazar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        $record->estado === Turno::ESTADO_PENDIENTE &&
                        ! self::estaVencido($record)
                    )
                    ->action(function ($record) {
                        if (self::estaVencido($record)) {
                            return;
                        }

                        $record->update(['estado' => Turno::ESTADO_RECHAZADO]);
                    }),
            ])
            ->paginated();
    }

    protected static function estaVencido($turno): bool
    {
        $fecha = $turno->fecha instanceof CarbonInterface
            ? $turno->fecha->copy()
            : Carbon::parse($turno->fecha);

        $horaFinStr = (string) $turno->hora_fin;

        // Si viene con fecha incluida, extraer solo hora
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr = Carbon::parse($horaFinStr)->format('H:i:s');
        }

        // Aseguramos HH:MM:SS
        if (preg_match('/^\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr .= ':00';
        }

        $finTurno = $fecha->copy()->setTimeFromTimeString($horaFinStr);

        return $finTurno->isPast();
    }
}
