<?php

namespace App\Filament\Profesor\Resources\Turnos\Tables;

use App\Mail\AlumnoClaseSuspendidaPorProfesor;
use App\Models\Pago;
use App\Models\Turno;
use App\Services\AuditLogger;
use App\Services\TurnoRespuestaProfesorService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TurnosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('alumno.name')
                    ->label('Alumno')
                    ->formatStateUsing(function ($state, Turno $record) {
                        $nombre = trim(($record->alumno?->name ?? '') . ' ' . ($record->alumno?->apellido ?? ''));
                        return $nombre !== '' ? $nombre : ($record->alumno?->name ?? '-');
                    })
                    ->searchable(),

                TextColumn::make('materia.materia_nombre')
                    ->label('Materia')
                    ->placeholder('-'),

                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

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
                        'success' => Turno::ESTADO_CONFIRMADO,
                        'danger'  => [
                            Turno::ESTADO_RECHAZADO,
                            Turno::ESTADO_SUSPENDIDO_PROFESOR,
                        ],
                        'gray'    => Turno::ESTADO_VENCIDO,
                    ])
                    ->formatStateUsing(function ($state, Turno $record) {
                        if (
                            $state === Turno::ESTADO_CONFIRMADO
                            && now()->gte($record->finDateTime())
                        ) {
                            return 'Finalizada';
                        }

                        if (
                            in_array((string) $state, [Turno::ESTADO_PENDIENTE, Turno::ESTADO_PENDIENTE_PAGO], true)
                            && app(TurnoRespuestaProfesorService::class)->estaVencido($record)
                        ) {
                            return 'Vencido';
                        }

                        return match ($state) {
                            Turno::ESTADO_PENDIENTE      => 'Pendiente',
                            Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
                            Turno::ESTADO_CONFIRMADO     => 'Clase pagada',
                            Turno::ESTADO_SUSPENDIDO_PROFESOR => 'Suspendido por profesor',
                            Turno::ESTADO_RECHAZADO      => 'Rechazado',
                            Turno::ESTADO_CANCELADO      => 'Cancelado',
                            Turno::ESTADO_VENCIDO        => 'Vencido',
                            Turno::ESTADO_ACEPTADO       => 'Aceptado (legacy)',
                            default => $state ? ucfirst((string) $state) : '-',
                        };
                    }),

                TextColumn::make('enlace_clase')
                    ->label('Enlace')
                    ->placeholder('-')
                    ->limit(35)
                    ->tooltip(fn ($state) => $state)
                    ->url(fn ($state) => filled($state) ? $state : null)
                    ->openUrlInNewTab()
                    ->copyable()
                    ->copyMessage('Enlace copiado')
                    ->toggleable(),
            ])

            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)

            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        Turno::ESTADO_PENDIENTE      => 'Pendiente',
                        Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
                        Turno::ESTADO_CONFIRMADO     => 'Clase pagada',
                        Turno::ESTADO_RECHAZADO      => 'Rechazado',
                        Turno::ESTADO_CANCELADO      => 'Cancelado',
                        Turno::ESTADO_VENCIDO        => 'Vencido',
                        Turno::ESTADO_SUSPENDIDO_PROFESOR => 'Suspendido por profesor',
                        Turno::ESTADO_ACEPTADO       => 'Aceptado (legacy)',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('materia_id')
                    ->label('Materia')
                    ->relationship('materia', 'materia_nombre')
                    ->searchable()
                    ->preload()
                    ->native(false),

                Tables\Filters\SelectFilter::make('alumno_id')
                    ->label('Alumno')
                    ->options(function () {
                        $profesorId = Auth::id();

                        $alumnos = DB::table('turnos')
                            ->join('users', 'users.id', '=', 'turnos.alumno_id')
                            ->where('turnos.profesor_id', $profesorId)
                            ->select('users.id', 'users.name', 'users.apellido')
                            ->distinct()
                            ->orderBy('users.name')
                            ->get();

                        return $alumnos->mapWithKeys(function ($u) {
                            $nombre = trim(($u->name ?? '') . ' ' . ($u->apellido ?? ''));
                            return [$u->id => ($nombre !== '' ? $nombre : ($u->name ?? 'Alumno'))];
                        })->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false),

                Tables\Filters\Filter::make('rango_fechas')
                    ->label('Fecha')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn (Builder $q, $desde) => $q->whereDate('fecha', '>=', $desde))
                            ->when($data['hasta'] ?? null, fn (Builder $q, $hasta) => $q->whereDate('fecha', '<=', $hasta));
                    }),
            ])

            ->recordActions([
                Action::make('aceptar')
                    ->label('Aceptar')
                    ->color('primary')
                    ->visible(fn (Turno $record) =>
                        app(TurnoRespuestaProfesorService::class)
                            ->puedeResponder($record, (int) Auth::id())
                    )
                    ->action(function (Turno $record) {
                        try {
                            app(TurnoRespuestaProfesorService::class)->aceptar(
                                $record,
                                (int) Auth::id(),
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('No se pudo aceptar la clase')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('suspender')
                    ->label('Suspender clase')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        TextInput::make('suspension_motivo')
                            ->label('Motivo de suspensión')
                            ->required()
                            ->maxLength(1000)
                            ->placeholder('Describe brevemente por qué se suspende la clase'),
                    ])
                    ->visible(fn (Turno $record): bool => self::puedeSuspender(
                        $record,
                        (int) Auth::id(),
                    ))
                    ->action(function (Turno $record, array $data) {
                        $profesorId = (int) Auth::id();

                        try {
                            $turnoSuspendido = DB::transaction(function () use ($record, $data, $profesorId): Turno {
                                $turnoBloqueado = Turno::query()
                                    ->whereKey($record->getKey())
                                    ->where('profesor_id', $profesorId)
                                    ->lockForUpdate()
                                    ->first();

                                if (! $turnoBloqueado) {
                                    throw ValidationException::withMessages([
                                        'turno' => 'La clase no pertenece al profesor autenticado.',
                                    ]);
                                }

                                $pagoAprobado = Pago::query()
                                    ->where('turno_id', $turnoBloqueado->getKey())
                                    ->where('estado', Pago::ESTADO_APROBADO)
                                    ->lockForUpdate()
                                    ->first(['pago_id']);

                                if (
                                    $turnoBloqueado->estado !== Turno::ESTADO_CONFIRMADO
                                    || ! $pagoAprobado
                                    || now()->gte($turnoBloqueado->finDateTime())
                                ) {
                                    throw ValidationException::withMessages([
                                        'turno' => 'Esta clase ya no puede suspenderse.',
                                    ]);
                                }

                                /** @var AuditLogger $audit */
                                $audit = app(AuditLogger::class);
                                $estadoAntes = (string) $turnoBloqueado->estado;

                                $turnoBloqueado->update([
                                    'estado' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
                                    'suspendido_at' => now(),
                                    'suspendido_por_id' => $profesorId,
                                    'suspension_motivo' => trim((string) $data['suspension_motivo']),
                                ]);

                                $audit->log('turno.suspendido_profesor', $turnoBloqueado, [
                                    'turno_id' => $turnoBloqueado->id,
                                    'profesor_id' => $turnoBloqueado->profesor_id,
                                    'alumno_id' => $turnoBloqueado->alumno_id,
                                    'estado_anterior' => $estadoAntes,
                                    'estado_nuevo' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
                                    'suspendido_por_id' => $profesorId,
                                    'suspension_motivo' => $turnoBloqueado->suspension_motivo,
                                    'fecha' => (string) $turnoBloqueado->fecha,
                                    'hora_inicio' => (string) $turnoBloqueado->hora_inicio,
                                    'hora_fin' => (string) $turnoBloqueado->hora_fin,
                                ]);

                                return $turnoBloqueado->load('alumno');
                            });
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('No se pudo suspender la clase')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->warning()
                                ->send();

                            return;
                        }

                        if ($turnoSuspendido->alumno?->email) {
                            Mail::to($turnoSuspendido->alumno->email)
                                ->send(new AlumnoClaseSuspendidaPorProfesor($turnoSuspendido));
                        }
                    }),

                Action::make('rechazar')
                    ->label('Rechazar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Turno $record) =>
                        app(TurnoRespuestaProfesorService::class)
                            ->puedeResponder($record, (int) Auth::id())
                    )
                    ->action(function (Turno $record) {
                        app(TurnoRespuestaProfesorService::class)->rechazar(
                            $record,
                            (int) Auth::id(),
                        );
                    }),
            ])
            ->paginated();
    }

    private static function puedeSuspender(Turno $turno, int $profesorId): bool
    {
        return (int) $turno->profesor_id === $profesorId
            && $turno->estado === Turno::ESTADO_CONFIRMADO
            && $turno->pago?->estado === Pago::ESTADO_APROBADO
            && now()->lt($turno->finDateTime());
    }

}
