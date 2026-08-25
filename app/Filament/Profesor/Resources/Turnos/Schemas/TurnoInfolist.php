<?php

namespace App\Filament\Profesor\Resources\Turnos\Schemas;

use App\Filament\Profesor\Resources\Turnos\TurnoResource;
use App\Models\Turno;
use App\Services\TurnoRespuestaProfesorService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TurnoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Solicitud de turno')
                    ->description(fn (Turno $record): string => "Turno #{$record->id}")
                    ->extraAttributes(['class' => 'mx-auto w-full max-w-4xl'])
                    ->afterHeader([
                        TextEntry::make('estado')
                            ->hiddenLabel()
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => static::estadoLabel($state))
                            ->color(fn (?string $state): string => static::estadoColor($state)),
                    ])
                    ->columns(2)
                    ->schema([
                        TextEntry::make('alumno')
                            ->label('Alumno')
                            ->state(function (Turno $record): string {
                                $nombre = trim(($record->alumno?->name ?? '').' '.($record->alumno?->apellido ?? ''));

                                return $nombre ?: '-';
                            }),

                        TextEntry::make('materia.materia_nombre')
                            ->label('Materia')
                            ->placeholder('-'),

                        TextEntry::make('tema.tema_nombre')
                            ->label('Tema')
                            ->placeholder('-'),

                        TextEntry::make('fecha')
                            ->label('Fecha')
                            ->date('d/m/Y'),

                        TextEntry::make('horario')
                            ->label('Horario')
                            ->state(fn (Turno $record): string => sprintf(
                                '%s - %s',
                                substr((string) $record->hora_inicio, 0, 5),
                                substr((string) $record->hora_fin, 0, 5),
                            )),

                        TextEntry::make('precio_por_hora')
                            ->label('Precio por hora')
                            ->money('ARS', locale: 'es_AR')
                            ->placeholder('-'),

                        TextEntry::make('precio_total')
                            ->label('Precio total')
                            ->money('ARS', locale: 'es_AR')
                            ->placeholder('-'),

                        TextEntry::make('enlace_clase')
                            ->label('Enlace de la clase')
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->openUrlInNewTab()
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('indicacion_respuesta')
                            ->hiddenLabel()
                            ->state('Revisá la información antes de responder.')
                            ->columnSpanFull(),
                    ])
                    ->footer([
                        Grid::make(2)
                            ->schema([
                                Actions::make([
                                    Action::make('volver')
                                        ->label('Volver al listado')
                                        ->color('gray')
                                        ->url(fn (): string => TurnoResource::getUrl('index', panel: 'profesor')),
                                ])->alignStart(),

                                Actions::make([
                                    Action::make('rechazar')
                                        ->label('Rechazar')
                                        ->color('danger')
                                        ->outlined()
                                        ->requiresConfirmation()
                                        ->visible(fn (Turno $record): bool => app(TurnoRespuestaProfesorService::class)
                                            ->puedeResponder($record, (int) Auth::id()))
                                        ->action(function (Turno $record): void {
                                            app(TurnoRespuestaProfesorService::class)->rechazar(
                                                $record,
                                                (int) Auth::id(),
                                            );

                                            $record->refresh();
                                        }),

                                    Action::make('aceptar')
                                        ->label('Aceptar')
                                        ->color('primary')
                                        ->visible(fn (Turno $record): bool => app(TurnoRespuestaProfesorService::class)
                                            ->puedeResponder($record, (int) Auth::id()))
                                        ->action(function (Turno $record): void {
                                            try {
                                                app(TurnoRespuestaProfesorService::class)->aceptar(
                                                    $record,
                                                    (int) Auth::id(),
                                                );
                                            } catch (ValidationException $exception) {
                                                Notification::make()
                                                    ->title('No se pudo aceptar la clase')
                                                    ->body(collect($exception->errors())->flatten()->first())
                                                    ->warning()
                                                    ->send();

                                                return;
                                            }

                                            $record->refresh();
                                        }),
                                ])->alignEnd(),
                            ]),
                    ]),
            ]);
    }

    private static function estadoLabel(?string $estado): string
    {
        return match ($estado) {
            Turno::ESTADO_PENDIENTE => 'Pendiente',
            Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
            Turno::ESTADO_CONFIRMADO => 'Clase pagada',
            Turno::ESTADO_RECHAZADO => 'Rechazado',
            Turno::ESTADO_CANCELADO => 'Cancelado',
            Turno::ESTADO_VENCIDO => 'Vencido',
            Turno::ESTADO_SUSPENDIDO_PROFESOR => 'Suspendido por profesor',
            Turno::ESTADO_ACEPTADO => 'Aceptado (legacy)',
            default => $estado ? ucfirst($estado) : '-',
        };
    }

    private static function estadoColor(?string $estado): string
    {
        return match ($estado) {
            Turno::ESTADO_PENDIENTE => 'warning',
            Turno::ESTADO_PENDIENTE_PAGO, Turno::ESTADO_ACEPTADO => 'primary',
            Turno::ESTADO_CONFIRMADO => 'success',
            Turno::ESTADO_RECHAZADO, Turno::ESTADO_SUSPENDIDO_PROFESOR => 'danger',
            Turno::ESTADO_CANCELADO, Turno::ESTADO_VENCIDO => 'gray',
            default => 'gray',
        };
    }
}
