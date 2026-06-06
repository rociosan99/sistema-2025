<?php

namespace App\Filament\Profesor\Resources\MisClases;

use App\Filament\Profesor\Resources\MisClases\Pages\ListMisClases;
use App\Filament\Profesor\Resources\MisClases\Pages\ViewMisClase;
use App\Filament\Profesor\Resources\MisClases\Tables\MisClasesTable;
use App\Models\Turno;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MisClaseResource extends Resource
{
    protected static ?string $model = Turno::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Mis Clases';
    protected static ?string $modelLabel = 'Clase';
    protected static ?string $pluralModelLabel = 'Mis Clases';
    protected static ?string $slug = 'mis-clases';
    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('profesor_id', Auth::id())
            ->where('estado', Turno::ESTADO_CONFIRMADO)
            ->where(function (Builder $query): void {
                $query
                    ->whereDate('fecha', '<', now()->toDateString())
                    ->orWhere(function (Builder $query): void {
                        $query
                            ->whereDate('fecha', now()->toDateString())
                            ->where('hora_fin', '<=', now()->format('H:i:s'));
                    });
            })
            ->with([
                'alumno',
                'materia',
                'tema',
                'pago',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Clase')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->date('d/m/Y'),

                        TextEntry::make('horario')
                            ->label('Horario')
                            ->state(fn (Turno $record): string => static::horario($record)),

                        TextEntry::make('alumno')
                            ->label('Alumno')
                            ->state(fn (Turno $record): string => static::nombreCompleto($record->alumno)),

                        TextEntry::make('materia.materia_nombre')
                            ->label('Materia')
                            ->placeholder('-'),

                        TextEntry::make('tema.tema_nombre')
                            ->label('Tema')
                            ->placeholder('-'),

                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (?string $state): string => static::estadoColor($state))
                            ->formatStateUsing(fn (?string $state): string => static::estadoLabel($state)),
                    ]),

                Section::make('Pago')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('pago.estado')
                            ->label('Estado del pago')
                            ->badge()
                            ->color(fn (?string $state): string => static::pagoEstadoColor($state))
                            ->formatStateUsing(fn (?string $state): string => static::pagoEstadoLabel($state))
                            ->placeholder('-'),

                        TextEntry::make('pago.monto')
                            ->label('Monto')
                            ->money(fn (Turno $record): string => $record->pago?->moneda ?? 'ARS', locale: 'es_AR')
                            ->placeholder('-'),

                        TextEntry::make('pago.moneda')
                            ->label('Moneda')
                            ->placeholder('-'),

                        TextEntry::make('pago.fecha_aprobado')
                            ->label('Fecha de aprobacion')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('pago.provider')
                            ->label('Provider')
                            ->placeholder('-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return MisClasesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMisClases::route('/'),
            'view' => ViewMisClase::route('/{record}'),
        ];
    }

    public static function horario(Turno $turno): string
    {
        $inicio = $turno->hora_inicio ? substr((string) $turno->hora_inicio, 0, 5) : null;
        $fin = $turno->hora_fin ? substr((string) $turno->hora_fin, 0, 5) : null;

        if (! $inicio && ! $fin) {
            return '-';
        }

        return trim("{$inicio} - {$fin}", ' -');
    }

    public static function nombreCompleto(?Model $user): string
    {
        $nombre = trim((string) ($user?->name ?? ''));
        $apellido = trim((string) ($user?->apellido ?? ''));

        return trim("{$nombre} {$apellido}") ?: '-';
    }

    public static function estadoLabel(?string $estado): string
    {
        return match ($estado) {
            Turno::ESTADO_CONFIRMADO => 'Clase finalizada',
            default => $estado ? ucfirst($estado) : '-',
        };
    }

    public static function estadoColor(?string $estado): string
    {
        return match ($estado) {
            Turno::ESTADO_CONFIRMADO => 'success',
            default => 'gray',
        };
    }

    public static function pagoEstadoLabel(?string $estado): string
    {
        return match ($estado) {
            'pendiente' => 'Pendiente',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            'error' => 'Error',
            default => $estado ? ucfirst($estado) : '-',
        };
    }

    public static function pagoEstadoColor(?string $estado): string
    {
        return match ($estado) {
            'aprobado' => 'success',
            'pendiente' => 'warning',
            'rechazado', 'error' => 'danger',
            default => 'gray',
        };
    }
}
