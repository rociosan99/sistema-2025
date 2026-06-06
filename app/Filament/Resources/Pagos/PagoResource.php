<?php

namespace App\Filament\Resources\Pagos;

use App\Filament\Resources\Pagos\Pages\ListPagos;
use App\Filament\Resources\Pagos\Pages\ViewPago;
use App\Filament\Resources\Pagos\Tables\PagosTable;
use App\Models\Pago;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PagoResource extends Resource
{
    protected static ?string $model = Pago::class;

    protected static ?string $modelLabel = 'Pago';
    protected static ?string $pluralModelLabel = 'Pagos';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Pagos';
    protected static string|\UnitEnum|null $navigationGroup = 'Administracion';
    protected static ?int $navigationSort = 40;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'turno.alumno',
                'turno.profesor',
                'turno.materia',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextEntry::make('pago_id')
                    ->label('Pago ID'),

                TextEntry::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state): string => static::estadoColor($state))
                    ->formatStateUsing(fn (?string $state): string => static::estadoLabel($state)),

                TextEntry::make('alumno')
                    ->label('Alumno')
                    ->state(fn (Pago $record): string => static::nombreCompleto($record->turno?->alumno)),

                TextEntry::make('profesor')
                    ->label('Profesor')
                    ->state(fn (Pago $record): string => static::nombreCompleto($record->turno?->profesor)),

                TextEntry::make('materia')
                    ->label('Materia')
                    ->state(fn (Pago $record): ?string => $record->turno?->materia?->materia_nombre)
                    ->placeholder('-'),

                TextEntry::make('fecha_clase')
                    ->label('Fecha de clase')
                    ->state(fn (Pago $record): mixed => $record->turno?->fecha)
                    ->date('d/m/Y')
                    ->placeholder('-'),

                TextEntry::make('horario')
                    ->label('Horario')
                    ->state(fn (Pago $record): string => static::horario($record))
                    ->placeholder('-'),

                TextEntry::make('monto')
                    ->label('Monto')
                    ->money(fn (Pago $record): string => $record->moneda ?? 'ARS', locale: 'es_AR')
                    ->placeholder('-'),

                TextEntry::make('fecha_aprobado')
                    ->label('Fecha de aprobacion')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return PagosTable::configure($table);
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
            'index' => ListPagos::route('/'),
            'view' => ViewPago::route('/{record}'),
        ];
    }

    public static function estadoOptions(): array
    {
        return [
            Pago::ESTADO_PENDIENTE => 'Pendiente',
            Pago::ESTADO_APROBADO => 'Aprobado',
            Pago::ESTADO_RECHAZADO => 'Rechazado',
            Pago::ESTADO_ERROR => 'Error',
        ];
    }

    public static function estadoLabel(?string $estado): string
    {
        return static::estadoOptions()[$estado] ?? (string) $estado;
    }

    public static function estadoColor(?string $estado): string
    {
        return match ($estado) {
            Pago::ESTADO_APROBADO => 'success',
            Pago::ESTADO_PENDIENTE => 'warning',
            Pago::ESTADO_RECHAZADO, Pago::ESTADO_ERROR => 'danger',
            default => 'gray',
        };
    }

    public static function nombreCompleto(?Model $user): string
    {
        $nombre = trim((string) ($user?->name ?? ''));
        $apellido = trim((string) ($user?->apellido ?? ''));

        return trim("{$nombre} {$apellido}") ?: '-';
    }

    public static function horario(Pago $pago): string
    {
        $inicio = $pago->turno?->hora_inicio;
        $fin = $pago->turno?->hora_fin;

        if (! $inicio && ! $fin) {
            return '-';
        }

        return trim("{$inicio} - {$fin}", ' -');
    }
}
