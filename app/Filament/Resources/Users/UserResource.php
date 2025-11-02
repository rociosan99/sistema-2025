<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;

// Forms (v4 usa Schema, pero los componentes siguen acá)
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

// Tables
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

// Acciones en v4
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Tipado requerido por v4
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $pluralLabel = 'Usuarios';
    protected static ?string $slug = 'usuarios';

    // En v4: form usa Schema
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Nombre')
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
                    'administrador' => 'Administrador',
                    'profesor' => 'Profesor',
                    'alumno'   => 'Alumno',
                ])
                ->required(),

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
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('role')->label('Rol')->sortable(),
                TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y'),
            ])
            // En v4 usar recordActions() (no actions())
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Eliminar'),
            ])
            // Bulk actions van en toolbar/header o groupedBulkActions()
            ->groupedBulkActions([
                BulkAction::make('delete')
                    ->label('Eliminar seleccionados')
                    ->requiresConfirmation()
                    ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->delete()),
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
