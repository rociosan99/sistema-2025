<?php

namespace App\Filament\Profesor\Resources\Turnos;

use App\Filament\Profesor\Resources\Turnos\Pages\ListTurnos;
use App\Filament\Profesor\Resources\Turnos\Tables\TurnosTable;
use App\Models\Turno;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TurnoResource extends Resource
{
    protected static ?string $model = Turno::class;

    protected static ?string $navigationLabel = 'Solicitudes de Turno';
    protected static ?string $pluralModelLabel = 'Solicitudes';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    /**
     * Mostrar todos los turnos del profesor (historial)
     * y vencer automáticamente los pendientes que ya pasaron.
     */
    public static function getEloquentQuery(): Builder
    {
        $profesorId = Auth::id();

        static::vencerTurnosPendientesDelProfesor($profesorId);

        return parent::getEloquentQuery()
            ->where('profesor_id', $profesorId)
            ->orderBy('fecha', 'desc')
            ->orderBy('hora_inicio', 'desc');
    }

    protected static function vencerTurnosPendientesDelProfesor(?int $profesorId): void
    {
        if (! $profesorId) {
            return;
        }

        $hoy = now()->toDateString();
        $horaActual = now()->format('H:i:s');

        Turno::query()
            ->where('profesor_id', $profesorId)
            ->where('estado', Turno::ESTADO_PENDIENTE)
            ->where(function ($query) use ($hoy, $horaActual) {
                $query->whereDate('fecha', '<', $hoy)
                    ->orWhere(function ($subQuery) use ($hoy, $horaActual) {
                        $subQuery->whereDate('fecha', $hoy)
                            ->where('hora_inicio', '<=', $horaActual);
                    });
            })
            ->update([
                'estado' => Turno::ESTADO_VENCIDO,
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return TurnosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTurnos::route('/'),
        ];
    }
}