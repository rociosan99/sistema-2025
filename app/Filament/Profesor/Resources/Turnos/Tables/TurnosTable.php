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
                    ->formatStateUsing(fn ($state) => $state ? substr((string) $state, 0, 5) : '-'),

                TextColumn::make('hora_fin')
                    ->label('Hasta')
                    ->formatStateUsing(fn ($state) => $state ? substr((string) $state, 0, 5) : '-'),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => Turno::ESTADO_PENDIENTE,
                        'primary' => Turno::ESTADO_PENDIENTE_PAGO,
                        'success' => Turno::ESTADO_CONFIRMADO, // pagado
                        'danger'  => Turno::ESTADO_RECHAZADO,
                        'gray'    => Turno::ESTADO_VENCIDO,
                    ])
                    ->formatStateUsing(function ($state, $record) {
                        // ✅ Regla: si no está pagado/cancelado/rechazado y ya pasó el horario -> vencido
                        if (
                            in_array($state, [Turno::ESTADO_PENDIENTE, Turno::ESTADO_PENDIENTE_PAGO], true)
                            && self::estaVencido($record)
                        ) {
                            return 'Vencido';
                        }

                        return match ($state) {
                            Turno::ESTADO_PENDIENTE => 'Pendiente',
                            Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
                            Turno::ESTADO_CONFIRMADO => 'Clase pagada',
                            Turno::ESTADO_RECHAZADO => 'Rechazado',
                            Turno::ESTADO_CANCELADO => 'Cancelado',
                            Turno::ESTADO_VENCIDO => 'Vencido',
                            default => $state ? ucfirst((string) $state) : '-',
                        };
                    }),
            ])
            ->recordActions([
                Action::make('aceptar')
                    ->label('Aceptar')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        $record->estado === Turno::ESTADO_PENDIENTE &&
                        ! self::estaVencido($record)
                    )
                    ->action(function ($record) {
                        if (self::estaVencido($record)) {
                            return;
                        }

                        // ✅ FLUJO A:
                        // El profe acepta -> habilita pago directo
                        $record->update(['estado' => Turno::ESTADO_PENDIENTE_PAGO]);
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

    /**
     * ✅ Vencido si ya pasó el fin del turno y NO está en estado final.
     * (En la tabla igual controlamos por estado, pero esto sirve para visibilidad y jobs)
     */
    protected static function estaVencido($turno): bool
    {
        // Si ya está en estado final, NO lo tratamos como vencido
        if (in_array((string) $turno->estado, [
            Turno::ESTADO_CONFIRMADO, // pagado
            Turno::ESTADO_CANCELADO,
            Turno::ESTADO_RECHAZADO,
            Turno::ESTADO_VENCIDO,
        ], true)) {
            return false;
        }

        $fecha = $turno->fecha instanceof CarbonInterface
            ? $turno->fecha->copy()
            : Carbon::parse($turno->fecha);

        $horaFinStr = (string) $turno->hora_fin;

        // Si viene con fecha incluida: "2026-01-22 19:00:00"
        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr = Carbon::parse($horaFinStr)->format('H:i:s');
        }

        // Si viene "19:00" -> "19:00:00"
        if (preg_match('/^\d{2}:\d{2}$/', $horaFinStr)) {
            $horaFinStr .= ':00';
        }

        $finTurno = $fecha->copy()->setTimeFromTimeString($horaFinStr);

        return $finTurno->isPast();
    }
}
