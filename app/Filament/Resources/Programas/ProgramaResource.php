<?php

namespace App\Filament\Resources\Programas;

use App\Filament\Resources\Programas\Pages\CreatePrograma;
use App\Filament\Resources\Programas\Pages\EditPrograma;
use App\Filament\Resources\Programas\Pages\ListProgramas;
use App\Filament\Resources\Programas\Schemas\ProgramaForm;
use App\Filament\Resources\Programas\Tables\ProgramasTable;
use App\Models\Programa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema; // ✅ Importación de la clase Schema
use Filament\Tables\Table;

class ProgramaResource extends Resource
{
    protected static ?string $model = Programa::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'Programa';
    protected static ?string $pluralModelLabel = 'Programas';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión Académica';
    protected static ?int $navigationSort = 40;

    // La firma debe aceptar y retornar Filament\Schemas\Schema
    public static function form(Schema $schema): Schema
    {
        // El IDE indica un error aquí porque a veces confunde la clase ProgramaForm
        // con la convención anterior de Forms. Sin embargo, el código es funcional.
        return ProgramaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramas::route('/'),
            'create' => CreatePrograma::route('/create'),
            'edit' => EditPrograma::route('/{record}/edit'),
        ];
    }
}