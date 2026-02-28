<?php

namespace App\Filament\Alumno\Resources\Turnos;

use App\Filament\Alumno\Resources\Turnos\Pages\ListTurnos;
use App\Models\Turno;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;

class TurnoResource extends Resource
{
    protected static ?string $model = Turno::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Turnos';
    protected static ?string $pluralLabel      = 'Turnos';
    protected static ?string $modelLabel       = 'Turno';
    protected static ?string $slug             = 'turnos';

    protected static ?string $recordTitleAttribute = 'fecha';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => Turno::ESTADO_PENDIENTE,
                        'info'    => Turno::ESTADO_ACEPTADO,
                        'primary' => Turno::ESTADO_PENDIENTE_PAGO,
                        'success' => Turno::ESTADO_CONFIRMADO,
                        'danger'  => Turno::ESTADO_RECHAZADO,
                        'gray'    => Turno::ESTADO_VENCIDO,
                    ])
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        Turno::ESTADO_PENDIENTE => 'Pendiente',
                        Turno::ESTADO_ACEPTADO => 'Aceptado',
                        Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
                        Turno::ESTADO_CONFIRMADO => 'Clase pagada',
                        Turno::ESTADO_RECHAZADO => 'Rechazado',
                        Turno::ESTADO_CANCELADO => 'Cancelado',
                        Turno::ESTADO_VENCIDO => 'Vencido',
                        default => $state ? ucfirst($state) : '-',
                    }),

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

                TextColumn::make('materia.materia_nombre')
                    ->label('Materia')
                    ->placeholder('-'),

                TextColumn::make('tema.tema_nombre')
                    ->label('Tema')
                    ->placeholder('-'),

                TextColumn::make('profesor.name')
                    ->label('Profesor')
                    ->formatStateUsing(function ($state, Turno $record) {
                        $nombre = trim(($record->profesor?->name ?? '') . ' ' . ($record->profesor?->apellido ?? ''));
                        return $nombre !== '' ? $nombre : ($record->profesor?->name ?? '-');
                    })
                    ->placeholder('-'),

                ViewColumn::make('acciones')
                    ->label('Acciones')
                    ->view('filament.alumno.turnos.acciones'),
            ])

            // ✅ filtros visibles arriba
            ->filtersLayout(FiltersLayout::AboveContent)

            // ✅ 3 columnas para que quede: Estado | Materia | Profesor
            // y luego: Desde | Hasta (baja solo)
            ->filtersFormColumns(3)

            ->filters([
                // ✅ Estado
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

                // ✅ Materia
                Tables\Filters\SelectFilter::make('materia_id')
                    ->label('Materia')
                    ->relationship('materia', 'materia_nombre')
                    ->searchable()
                    ->preload()
                    ->native(false),

                // ✅ Profesor (nombre + apellido)
                Tables\Filters\SelectFilter::make('profesor_id')
                    ->label('Profesor')
                    ->options(function () {
                        return User::query()
                            ->where('role', 'profesor')
                            ->orderBy('name')
                            ->get(['id', 'name', 'apellido'])
                            ->mapWithKeys(function ($u) {
                                $nombre = trim(($u->name ?? '') . ' ' . ($u->apellido ?? ''));
                                return [$u->id => ($nombre !== '' ? $nombre : ($u->name ?? 'Profesor'))];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false),

                // ✅ Rango de fechas (desde / hasta)
                Tables\Filters\Filter::make('rango_fechas')
                    ->label('Fecha')
                    ->form([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, fn (Builder $q, $desde) => $q->whereDate('fecha', '>=', $desde))
                            ->when($data['hasta'] ?? null, fn (Builder $q, $hasta) => $q->whereDate('fecha', '<=', $hasta));
                    }),
            ])

            ->defaultSort('fecha', 'asc')
            ->emptyStateHeading('No tenés turnos aún')
            ->emptyStateDescription('Solicitá un turno desde el botón "Solicitar turno".');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('alumno_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTurnos::route('/'),
        ];
    }
}
