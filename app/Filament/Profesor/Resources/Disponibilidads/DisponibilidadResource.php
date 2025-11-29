<?php

namespace App\Filament\Profesor\Resources\Disponibilidads;

use App\Filament\Profesor\Resources\Disponibilidads\Pages\CreateDisponibilidad;
use App\Filament\Profesor\Resources\Disponibilidads\Pages\EditDisponibilidad;
use App\Filament\Profesor\Resources\Disponibilidads\Pages\ListDisponibilidads;
use App\Filament\Profesor\Resources\Disponibilidads\Schemas\DisponibilidadForm;
use App\Filament\Profesor\Resources\Disponibilidads\Tables\DisponibilidadsTable;
use App\Models\Disponibilidad;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DisponibilidadResource extends Resource
{
    protected static ?string $model = Disponibilidad::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Disponibilidad';
    protected static ?string $pluralLabel = 'Disponibilidad';
    protected static ?string $modelLabel = 'Disponibilidad';
    protected static ?string $slug = 'disponibilidad';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return DisponibilidadForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DisponibilidadsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDisponibilidads::route('/'),
            'create' => CreateDisponibilidad::route('/create'),
            'edit'   => EditDisponibilidad::route('/{record}/edit'),
        ];
    }
}
