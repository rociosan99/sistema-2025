<?php

namespace App\Filament\Alumno\Resources\Turnos;

use App\Filament\Alumno\Resources\Turnos\Pages\ListTurnos;
use App\Models\Turno;
use App\Models\User;
use App\Services\CreditoService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
    protected static ?string $pluralLabel = 'Turnos';
    protected static ?string $modelLabel = 'Turno';
    protected static ?string $slug = 'turnos';

    protected static ?string $recordTitleAttribute = 'fecha';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        $politicaCancelacion = null;
        $politicaCancelacionError = null;

        try {
            $creditoService = app(CreditoService::class);
            $politica = $creditoService->obtenerPoliticaVigente();

            $politicaCancelacion = [
                'horas_sin_penalizacion' => (int) $politica->horas_cancelacion_sin_penalizacion,
                'porcentaje_credito_anticipado' => (float) $politica->porcentaje_credito_anticipado,
                'porcentaje_credito_tardio' => (float) $politica->porcentaje_credito_tardio,
                'porcentaje_penalizacion' => (float) $politica->porcentaje_penalizacion_tardia,
                'vigencia_creditos_dias' => (int) $politica->vigencia_creditos_dias,
                'version' => $creditoService->huellaPolitica($politica),
            ];
        } catch (ValidationException) {
            $politicaCancelacionError = 'La política de suspensión no está disponible o es inválida.';
        }

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
                        'secondary' => Turno::ESTADO_SUSPENDIDO_PROFESOR,
                        'danger'  => Turno::ESTADO_RECHAZADO,
                        'gray'    => Turno::ESTADO_VENCIDO,
                    ])
                    ->formatStateUsing(fn (?string $state, Turno $record) => match ($state) {
                        Turno::ESTADO_PENDIENTE => 'Pendiente',
                        Turno::ESTADO_ACEPTADO => 'Aceptado',
                        Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
                        Turno::ESTADO_CONFIRMADO => 'Clase pagada',
                        Turno::ESTADO_SUSPENDIDO_PROFESOR => 'Suspendido por profesor',
                        Turno::ESTADO_RECHAZADO => 'Rechazado',
                        Turno::ESTADO_CANCELADO => $record->reprogramado_por_turno_id
                            ? 'Reprogramado'
                            : ($record->cancelacion_tipo ? 'Suspendida por el alumno' : 'Cancelado'),
                        Turno::ESTADO_VENCIDO => 'Vencido',
                        default => $state ? ucfirst($state) : '-',
                    })
                    ->extraCellAttributes(['class' => 'py-3']),

                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->extraCellAttributes(['class' => 'py-3']),

                TextColumn::make('hora_inicio')
                    ->label('Horario')
                    ->formatStateUsing(function ($state, Turno $record): string {
                        $desde = $record->hora_inicio
                            ? substr((string) $record->hora_inicio, 0, 5)
                            : '-';
                        $hasta = $record->hora_fin
                            ? substr((string) $record->hora_fin, 0, 5)
                            : '-';

                        return "{$desde} - {$hasta}";
                    })
                    ->extraCellAttributes(['class' => 'py-3']),

                TextColumn::make('materia.materia_nombre')
                    ->label('Clase')
                    ->description(fn (Turno $record): ?string => $record->tema?->tema_nombre)
                    ->wrap()
                    ->extraCellAttributes(['class' => 'py-3'])
                    ->placeholder('-'),

                TextColumn::make('profesor.name')
                    ->label('Profesor')
                    ->formatStateUsing(function ($state, Turno $record) {
                        $profesor = $record->profesor;

                        if (! $profesor) {
                            return '-';
                        }

                        $nombre = trim(($profesor->name ?? '') . ' ' . ($profesor->apellido ?? ''));

                        if ($nombre === '') {
                            $nombre = $profesor->name ?? '-';
                        }

                        if (isset($profesor->activo) && ! $profesor->activo) {
                            $nombre .= ' (dado de baja)';
                        }

                        return $nombre;
                    })
                    ->extraCellAttributes(['class' => 'py-3'])
                    ->placeholder('-'),

                ViewColumn::make('acciones')
                    ->label('Acciones')
                    ->view('filament.alumno.turnos.acciones')
                    ->viewData([
                        'politicaCancelacion' => $politicaCancelacion,
                        'politicaCancelacionError' => $politicaCancelacionError,
                    ])
                    ->extraCellAttributes(['class' => 'py-3']),
            ])
            ->recordClasses('border-b border-gray-200 dark:border-white/10')
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        Turno::ESTADO_PENDIENTE      => 'Pendiente',
                        Turno::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
                        Turno::ESTADO_CONFIRMADO     => 'Clase pagada',
                        Turno::ESTADO_SUSPENDIDO_PROFESOR => 'Suspendido por profesor',
                        Turno::ESTADO_RECHAZADO      => 'Rechazado',
                        Turno::ESTADO_CANCELADO      => 'Suspendida / reprogramada',
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

                Tables\Filters\SelectFilter::make('profesor_id')
                    ->label('Profesor')
                    ->options(function () {
                        return User::query()
                            ->where('role', 'profesor')
                            ->orderBy('name')
                            ->get(['id', 'name', 'apellido', 'activo'])
                            ->mapWithKeys(function ($u) {
                                $nombre = trim(($u->name ?? '') . ' ' . ($u->apellido ?? ''));
                                $nombre = $nombre !== '' ? $nombre : ($u->name ?? 'Profesor');

                                if (isset($u->activo) && ! $u->activo) {
                                    $nombre .= ' (dado de baja)';
                                }

                                return [$u->id => $nombre];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false),

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
            ->emptyStateHeading('No tenés turnos aún')
            ->emptyStateDescription('Solicitá un turno desde el botón "Solicitar turno".');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('alumno_id', Auth::id())
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->orderByDesc('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTurnos::route('/'),
        ];
    }
}
