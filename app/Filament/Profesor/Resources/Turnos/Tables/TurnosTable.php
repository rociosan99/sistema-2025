<?php

namespace App\Filament\Profesor\Resources\Turnos\Tables;

use App\Mail\AlumnoClaseSuspendidaPorProfesor;
use App\Models\Turno;
use App\Services\AuditLogger;
use App\Services\TurnoRespuestaProfesorService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
                    ->form([
                        TextInput::make('enlace_clase')
                            ->label('Enlace de la clase')
                            ->placeholder('https://meet.google.com/... o https://zoom.us/...')
                            ->required()
                            ->url()
                            ->maxLength(2048),
                    ])
                    ->visible(fn (Turno $record) =>
                        app(TurnoRespuestaProfesorService::class)
                            ->puedeResponder($record, (int) Auth::id())
                    )
                    ->action(function (Turno $record, array $data) {
                        app(TurnoRespuestaProfesorService::class)->aceptar(
                            $record,
                            (int) Auth::id(),
                            (string) $data['enlace_clase'],
                        );
                    }),

                Action::make('editarEnlace')
                    ->label('Editar enlace')
                    ->color('gray')
                    ->form([
                        TextInput::make('enlace_clase')
                            ->label('Enlace de la clase')
                            ->placeholder('https://meet.google.com/... o https://zoom.us/...')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->default(fn (Turno $record) => $record->enlace_clase),
                    ])
                    ->visible(fn (Turno $record) =>
                        in_array($record->estado, [
                            Turno::ESTADO_PENDIENTE_PAGO,
                            Turno::ESTADO_CONFIRMADO,
                            Turno::ESTADO_ACEPTADO,
                        ], true)
                    )
                    ->action(function (Turno $record, array $data) {
                        /** @var AuditLogger $audit */
                        $audit = app(AuditLogger::class);

                        $enlaceAnterior = $record->enlace_clase;

                        $record->update([
                            'enlace_clase' => trim((string) $data['enlace_clase']),
                        ]);

                        $audit->log('turno.enlace_clase_actualizado', $record, [
                            'turno_id' => $record->id,
                            'profesor_id' => $record->profesor_id,
                            'alumno_id' => $record->alumno_id,
                            'enlace_anterior' => $enlaceAnterior,
                            'enlace_nuevo' => $record->enlace_clase,
                        ]);
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
                    ->visible(fn (Turno $record) =>
                        $record->estado === Turno::ESTADO_CONFIRMADO &&
                        ! app(TurnoRespuestaProfesorService::class)->estaVencido($record)
                    )
                    ->action(function (Turno $record, array $data) {
                        if ($record->estado !== Turno::ESTADO_CONFIRMADO) {
                            return;
                        }

                        /** @var AuditLogger $audit */
                        $audit = app(AuditLogger::class);

                        $estadoAntes = (string) $record->estado;

                        $record->update([
                            'estado' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
                            'suspendido_at' => now(),
                            'suspendido_por_id' => Auth::id(),
                            'suspension_motivo' => trim((string) $data['suspension_motivo']),
                        ]);

                        $audit->log('turno.suspendido_profesor', $record, [
                            'turno_id' => $record->id,
                            'profesor_id' => $record->profesor_id,
                            'alumno_id' => $record->alumno_id,
                            'estado_anterior' => $estadoAntes,
                            'estado_nuevo' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
                            'suspendido_por_id' => Auth::id(),
                            'suspension_motivo' => $record->suspension_motivo,
                            'fecha' => (string) $record->fecha,
                            'hora_inicio' => (string) $record->hora_inicio,
                            'hora_fin' => (string) $record->hora_fin,
                        ]);

                        $emailAlumno = $record->alumno?->email;
                        if ($emailAlumno) {
                            Mail::to($emailAlumno)->send(new AlumnoClaseSuspendidaPorProfesor($record));
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

}
