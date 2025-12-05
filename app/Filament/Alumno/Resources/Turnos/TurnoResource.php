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
                // 🔹 Estado del turno (pendiente / confirmado / cancelado)
                \Filament\Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? ucfirst($state) : '-')
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'confirmado',
                        'danger'  => 'cancelado',
                    ]),

                \Filament\Tables\Columns\TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('hora_inicio')
                    ->label('Desde')
                    ->time(),

                \Filament\Tables\Columns\TextColumn::make('hora_fin')
                    ->label('Hasta')
                    ->time(),

                \Filament\Tables\Columns\TextColumn::make('materia.materia_nombre')
                    ->label('Materia'),

                \Filament\Tables\Columns\TextColumn::make('tema.tema_nombre')
                    ->label('Tema'),

                \Filament\Tables\Columns\TextColumn::make('profesor.name')
                    ->label('Profesor'),
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
