<?php

namespace App\Filament\Profesor\Resources\Turnos\Tables;

use App\Mail\ProfesorRespondioTurno;
use App\Models\Turno;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonInterface;
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
                        'danger'  => Turno::ESTADO_RECHAZADO,
                        'gray'    => Turno::ESTADO_VENCIDO,
                    ])
                    ->formatStateUsing(function ($state, Turno $record) {
                        if (
                            in_array((string) $state, [Turno::ESTADO_PENDIENTE, Turno::ESTADO_PENDIENTE_PAGO], true)
                            && self::estaVencido($record)
                        ) {
                            return 'Vencido';
                        }

                        return match ($state) {
                            Turno::ESTADO_PENDIENTE      => 'Pendiente',
                            Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
                            Turno::ESTADO_CONFIRMADO     => 'Clase pagada',
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
                        $record->estado === Turno::ESTADO_PENDIENTE &&
                        ! self::estaVencido($record)
                    )
                    ->action(function (Turno $record, array $data) {
                        if (self::marcarComoVencidoSiCorresponde($record)) {
                            return;
                        }

                        /** @var AuditLogger $audit */
                        $audit = app(AuditLogger::class);

                        $estadoAntes = (string) $record->estado;

                        $record->update([
                            'estado' => Turno::ESTADO_PENDIENTE_PAGO,
                            'enlace_clase' => trim((string) $data['enlace_clase']),
                        ]);

                        $record->loadMissing(['alumno', 'profesor', 'materia', 'tema']);

                        $audit->log('turno.aceptado_profesor', $record, [
                            'turno_id' => $record->id,
                            'profesor_id' => $record->profesor_id,
                            'alumno_id' => $record->alumno_id,
                            'estado_anterior' => $estadoAntes,
                            'estado_nuevo' => Turno::ESTADO_PENDIENTE_PAGO,
                            'enlace_clase' => $record->enlace_clase,
                            'fecha' => (string) $record->fecha,
                            'hora_inicio' => (string) $record->hora_inicio,
                            'hora_fin' => (string) $record->hora_fin,
                        ]);

                        $emailAlumno = $record->alumno?->email;
                        if ($emailAlumno) {
                            Mail::to($emailAlumno)->send(new ProfesorRespondioTurno($record));
                        }
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

                Action::make('rechazar')
                    ->label('Rechazar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Turno $record) =>
                        $record->estado === Turno::ESTADO_PENDIENTE &&
                        ! self::estaVencido($record)
                    )
                    ->action(function (Turno $record) {
                        if (self::marcarComoVencidoSiCorresponde($record)) {
                            return;
                        }

                        /** @var AuditLogger $audit */
                        $audit = app(AuditLogger::class);

                        $estadoAntes = (string) $record->estado;

                        $record->update([
                            'estado' => Turno::ESTADO_RECHAZADO,
                        ]);

                        $record->loadMissing(['alumno', 'profesor', 'materia', 'tema']);

                        $audit->log('turno.rechazado_profesor', $record, [
                            'turno_id' => $record->id,
                            'profesor_id' => $record->profesor_id,
                            'alumno_id' => $record->alumno_id,
                            'estado_anterior' => $estadoAntes,
                            'estado_nuevo' => Turno::ESTADO_RECHAZADO,
                            'fecha' => (string) $record->fecha,
                            'hora_inicio' => (string) $record->hora_inicio,
                            'hora_fin' => (string) $record->hora_fin,
                        ]);

                        $emailAlumno = $record->alumno?->email;
                        if ($emailAlumno) {
                            Mail::to($emailAlumno)->send(new ProfesorRespondioTurno($record));
                        }
                    }),
            ])
            ->paginated();
    }

    protected static function estaVencido(Turno $turno): bool
    {
        if (in_array((string) $turno->estado, [
            Turno::ESTADO_CONFIRMADO,
            Turno::ESTADO_CANCELADO,
            Turno::ESTADO_RECHAZADO,
            Turno::ESTADO_VENCIDO,
        ], true)) {
            return false;
        }

        $fecha = $turno->fecha instanceof CarbonInterface
            ? $turno->fecha->copy()
            : Carbon::parse($turno->fecha);

        $horaInicioStr = (string) $turno->hora_inicio;

        if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $horaInicioStr)) {
            $horaInicioStr = Carbon::parse($horaInicioStr)->format('H:i:s');
        }

        if (preg_match('/^\d{2}:\d{2}$/', $horaInicioStr)) {
            $horaInicioStr .= ':00';
        }

        $inicioTurno = $fecha->copy()->setTimeFromTimeString($horaInicioStr);

        return now()->gte($inicioTurno);
    }

    protected static function marcarComoVencidoSiCorresponde(Turno $turno): bool
    {
        if (
            $turno->estado === Turno::ESTADO_PENDIENTE &&
            self::estaVencido($turno)
        ) {
            /** @var AuditLogger $audit */
            $audit = app(AuditLogger::class);

            $estadoAntes = (string) $turno->estado;

            $turno->update([
                'estado' => Turno::ESTADO_VENCIDO,
            ]);

            $audit->log('turno.vencido', $turno, [
                'turno_id' => $turno->id,
                'motivo' => 'respuesta_profesor_fuera_de_hora',
                'estado_anterior' => $estadoAntes,
                'estado_nuevo' => Turno::ESTADO_VENCIDO,
                'fecha' => (string) $turno->fecha,
                'hora_inicio' => (string) $turno->hora_inicio,
                'hora_fin' => (string) $turno->hora_fin,
            ]);

            return true;
        }

        return false;
    }
}