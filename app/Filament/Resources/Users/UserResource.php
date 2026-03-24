<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $pluralLabel = 'Usuarios';
    protected static ?string $slug = 'usuarios';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            TextInput::make('apellido')
                ->label('Apellido')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('Correo electrónico')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            Select::make('role')
                ->label('Rol')
                ->options([
                    'admin' => 'Administrador',
                    'profesor' => 'Profesor',
                    'alumno' => 'Alumno',
                ])
                ->required(),

            Select::make('activo')
                ->label('Estado')
                ->options([
                    1 => 'Activo',
                    0 => 'Dado de baja',
                ])
                ->required()
                ->default(1),

            TextInput::make('password')
                ->label('Contraseña')
                ->password()
                ->required(fn ($record) => $record === null)
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('apellido')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable(),

                TextColumn::make('role')
                    ->label('Rol')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Administrador',
                        'profesor' => 'Profesor',
                        'alumno' => 'Alumno',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('activo')
                    ->label('Estado')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Dado de baja')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y'),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),

                Action::make('darDeBaja')
                    ->label('Dar de baja')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->activo)
                    ->action(function (User $record): void {
                        $record->update(['activo' => false]);
                    }),

                Action::make('darDeAlta')
                    ->label('Dar de alta')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! $record->activo)
                    ->action(function (User $record): void {
                        $record->update(['activo' => true]);
                    }),
            ])
            ->groupedBulkActions([
                BulkAction::make('darDeBajaSeleccionados')
                    ->label('Dar de baja seleccionados')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each(function (User $record): void {
                            $record->update(['activo' => false]);
                        });
                    }),

                BulkAction::make('darDeAltaSeleccionados')
                    ->label('Dar de alta seleccionados')
                    ->requiresConfirmation()
                    ->action(function (Collection $records): void {
                        $records->each(function (User $record): void {
                            $record->update(['activo' => true]);
                        });
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/crear'),
            'edit'   => EditUser::route('/{record}/editar'),
        ];
    }
}