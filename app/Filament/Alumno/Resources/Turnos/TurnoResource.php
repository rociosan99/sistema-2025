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
use Filament\Actions\Action;

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
                        'warning' => 'pendiente',
                        'info'    => 'aceptado',
                        'primary' => 'pendiente_pago',
                        'success' => 'confirmado',
                        'danger'  => 'rechazado',
                        'gray'    => 'vencido',
                    ])
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'aceptado' => 'Aceptado (esperando confirmación del alumno)',
                        'pendiente_pago' => 'Pendiente de pago',
                        'confirmado' => 'Clase Pagada',
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

                TextColumn::make('tema.tema_nombre')
                    ->label('Tema')
                    ->placeholder('-'),

                TextColumn::make('profesor.name')
                    ->label('Profesor')
                    ->placeholder('-'),
            ])
            ->actions([
                Action::make('pagar')
                    ->label('Pagar')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->visible(fn (Turno $record) => $record->estado === 'pendiente_pago')
                    ->url(fn (Turno $record) => route('mp.pagar', ['turno' => $record->id]))
                    ->openUrlInNewTab(),
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
