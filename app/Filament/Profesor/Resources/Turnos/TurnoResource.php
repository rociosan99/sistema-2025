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

    protected static \BackedEnum | string | null $navigationIcon = 'heroicon-o-calendar-days';

    /**
     * 🔹 Mostrar TODOS los turnos del profesor (historial)
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('profesor_id', Auth::id())
            ->orderBy('fecha', 'desc');
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
