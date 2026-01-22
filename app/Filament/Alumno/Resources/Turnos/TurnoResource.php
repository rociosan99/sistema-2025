<?php

namespace App\Filament\Alumno\Resources\Turnos;

use App\Filament\Alumno\Resources\Turnos\Pages\ListTurnos;
use App\Models\Turno;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

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

    /**
     * TABLA — Listado de turnos del alumno.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'warning' => 'pendiente',
                        'info'    => 'aceptado',
                        'primary' => 'pendiente_pago',
                        'success' => 'confirmado', // pago OK
                        'danger'  => 'rechazado',
                        'gray'    => 'vencido',
                    ])
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'aceptado' => 'Aceptado (pendiente de pago)',
                        'pendiente_pago' => 'Pendiente de pago',
                        'confirmado' => 'Confirmado (pago OK)',
                        'rechazado' => 'Rechazado',
                        'cancelado' => 'Cancelado',
                        'vencido' => 'Vencido',
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

                // ✅ Tema puede ser null ahora, así que mostramos "-" cuando no exista
                TextColumn::make('tema.tema_nombre')
                    ->label('Tema')
                    ->placeholder('-'),

                TextColumn::make('profesor.name')
                    ->label('Profesor')
                    ->placeholder('-'),
            ])
            ->defaultSort('fecha', 'asc')
            ->emptyStateHeading('No tenés turnos aún')
            ->emptyStateDescription('Solicitá un turno desde el botón "Solicitar turno".');
    }

    /**
     * Solo mostrar los turnos del alumno logueado.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('alumno_id', Auth::id());
    }

    /**
     * SOLO página index (el listado).
     */
    public static function getPages(): array
    {
        return [
            'index' => ListTurnos::route('/'),
        ];
    }
}
