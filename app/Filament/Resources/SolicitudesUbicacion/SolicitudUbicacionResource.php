<?php

namespace App\Filament\Resources\SolicitudesUbicacion;

use App\Filament\Resources\SolicitudesUbicacion\Pages\ListSolicitudesUbicacion;
use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\SolicitudUbicacion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class SolicitudUbicacionResource extends Resource
{
    protected static ?string $model = SolicitudUbicacion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Solicitudes de ubicacion';
    protected static ?string $modelLabel = 'Solicitud de ubicacion';
    protected static ?string $pluralModelLabel = 'Solicitudes de ubicacion';
    protected static string|UnitEnum|null $navigationGroup = 'Administracion';
    protected static ?int $navigationSort = 30;

    public static function getNavigationBadge(): ?string
    {
        $count = SolicitudUbicacion::query()
            ->where('estado', SolicitudUbicacion::ESTADO_PENDIENTE)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        SolicitudUbicacion::TIPO_PAIS => 'Pais',
                        SolicitudUbicacion::TIPO_PROVINCIA => 'Provincia',
                        SolicitudUbicacion::TIPO_CIUDAD => 'Ciudad',
                        default => $state,
                    }),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SolicitudUbicacion::ESTADO_PENDIENTE => 'warning',
                        SolicitudUbicacion::ESTADO_APROBADA => 'success',
                        SolicitudUbicacion::ESTADO_RECHAZADA => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('nombre_solicitado')
                    ->label('Nombre solicitado'),

                TextColumn::make('pais.pais_nombre')
                    ->label('Pais contexto')
                    ->placeholder('-'),

                TextColumn::make('provincia.provincia_nombre')
                    ->label('Provincia contexto')
                    ->placeholder('-'),

                TextColumn::make('solicitante.email')
                    ->label('Solicitante')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Solicitada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('revisado_at')
                    ->label('Revisada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        SolicitudUbicacion::ESTADO_PENDIENTE => 'Pendiente',
                        SolicitudUbicacion::ESTADO_APROBADA => 'Aprobada',
                        SolicitudUbicacion::ESTADO_RECHAZADA => 'Rechazada',
                    ])
                    ->default(SolicitudUbicacion::ESTADO_PENDIENTE),

                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        SolicitudUbicacion::TIPO_PAIS => 'Pais',
                        SolicitudUbicacion::TIPO_PROVINCIA => 'Provincia',
                        SolicitudUbicacion::TIPO_CIUDAD => 'Ciudad',
                    ]),
            ])
            ->recordActions([
                Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (SolicitudUbicacion $record): bool => $record->estado === SolicitudUbicacion::ESTADO_PENDIENTE)
                    ->action(fn (SolicitudUbicacion $record) => static::aprobarSolicitud($record)),

                Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (SolicitudUbicacion $record): bool => $record->estado === SolicitudUbicacion::ESTADO_PENDIENTE)
                    ->form([
                        Textarea::make('observacion_admin')
                            ->label('Observacion')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(fn (SolicitudUbicacion $record, array $data) => static::rechazarSolicitud($record, $data)),
            ]);
    }

    public static function aprobarSolicitud(SolicitudUbicacion $record): void
    {
        DB::transaction(function () use ($record) {
            $record->refresh();

            if ($record->estado !== SolicitudUbicacion::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages(['estado' => 'La solicitud ya fue revisada.']);
            }

            match ($record->tipo) {
                SolicitudUbicacion::TIPO_PAIS => static::aprobarPais($record),
                SolicitudUbicacion::TIPO_PROVINCIA => static::aprobarProvincia($record),
                SolicitudUbicacion::TIPO_CIUDAD => static::aprobarCiudad($record),
                default => throw ValidationException::withMessages(['tipo' => 'Tipo de solicitud invalido.']),
            };

            $record->estado = SolicitudUbicacion::ESTADO_APROBADA;
            $record->revisado_por_id = Auth::id();
            $record->revisado_at = now();
            $record->save();
        });

        Notification::make()
            ->title('Solicitud aprobada')
            ->success()
            ->send();
    }

    public static function rechazarSolicitud(SolicitudUbicacion $record, array $data): void
    {
        DB::transaction(function () use ($record, $data) {
            $record->refresh();

            if ($record->estado !== SolicitudUbicacion::ESTADO_PENDIENTE) {
                throw ValidationException::withMessages(['estado' => 'La solicitud ya fue revisada.']);
            }

            $record->estado = SolicitudUbicacion::ESTADO_RECHAZADA;
            $record->observacion_admin = $data['observacion_admin'] ?? null;
            $record->revisado_por_id = Auth::id();
            $record->revisado_at = now();
            $record->save();
        });

        Notification::make()
            ->title('Solicitud rechazada')
            ->success()
            ->send();
    }

    private static function aprobarPais(SolicitudUbicacion $record): void
    {
        $nombre = mb_convert_case(trim((string) $record->nombre_pais_solicitado), MB_CASE_TITLE, "UTF-8");

        if ($nombre === '') {
            throw ValidationException::withMessages(['nombre_pais_solicitado' => 'La solicitud no tiene un pais informado.']);
        }

        if (Pais::query()->where('pais_nombre', $nombre)->exists()) {
            throw ValidationException::withMessages(['nombre_pais_solicitado' => 'Ya existe un pais con ese nombre.']);
        }

        $pais = Pais::create(['pais_nombre' => $nombre]);
        $record->pais_creado_id = $pais->pais_id;
    }

    private static function aprobarProvincia(SolicitudUbicacion $record): void
    {
        $nombre = mb_convert_case(trim((string) $record->nombre_provincia_solicitada), MB_CASE_TITLE, "UTF-8");

        if (! $record->pais_id) {
            throw ValidationException::withMessages(['pais_id' => 'La solicitud no tiene un pais asociado.']);
        }

        if ($nombre === '') {
            throw ValidationException::withMessages(['nombre_provincia_solicitada' => 'La solicitud no tiene una provincia informada.']);
        }

        if (! Pais::query()->where('pais_id', $record->pais_id)->exists()) {
            throw ValidationException::withMessages(['pais_id' => 'El pais asociado ya no existe.']);
        }

        if (Provincia::query()->where('pais_id', $record->pais_id)->where('provincia_nombre', $nombre)->exists()) {
            throw ValidationException::withMessages(['nombre_provincia_solicitada' => 'Ya existe una provincia con ese nombre para el pais indicado.']);
        }

        $provincia = Provincia::create([
            'pais_id' => $record->pais_id,
            'provincia_nombre' => $nombre,
        ]);

        $record->provincia_creada_id = $provincia->provincia_id;
    }

    private static function aprobarCiudad(SolicitudUbicacion $record): void
    {
        $nombre = mb_convert_case(trim((string) $record->nombre_ciudad_solicitada), MB_CASE_TITLE, "UTF-8");

        if (! $record->pais_id || ! $record->provincia_id) {
            throw ValidationException::withMessages(['provincia_id' => 'La solicitud no tiene pais y provincia asociados.']);
        }

        if ($nombre === '') {
            throw ValidationException::withMessages(['nombre_ciudad_solicitada' => 'La solicitud no tiene una ciudad informada.']);
        }

        $provinciaValida = Provincia::query()
            ->where('provincia_id', $record->provincia_id)
            ->where('pais_id', $record->pais_id)
            ->exists();

        if (! $provinciaValida) {
            throw ValidationException::withMessages(['provincia_id' => 'La provincia no pertenece al pais indicado.']);
        }

        if (Ciudad::query()->where('provincia_id', $record->provincia_id)->where('ciudad_nombre', $nombre)->exists()) {
            throw ValidationException::withMessages(['nombre_ciudad_solicitada' => 'Ya existe una ciudad con ese nombre para la provincia indicada.']);
        }

        $ciudad = Ciudad::create([
            'provincia_id' => $record->provincia_id,
            'ciudad_nombre' => $nombre,
        ]);

        $record->ciudad_creada_id = $ciudad->ciudad_id;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSolicitudesUbicacion::route('/'),
        ];
    }
}