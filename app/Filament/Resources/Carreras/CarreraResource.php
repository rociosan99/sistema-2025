<?php

namespace App\Filament\Resources\Carreras;

use App\Filament\Resources\Carreras\Pages\CreateCarrera;
use App\Filament\Resources\Carreras\Pages\EditCarrera;
use App\Filament\Resources\Carreras\Pages\ListCarreras;
use App\Filament\Resources\Carreras\Schemas\CarreraForm;
use App\Filament\Resources\Carreras\Tables\CarrerasTable;
use App\Models\Carrera;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CarreraResource extends Resource
{
    // 🔗 Modelo asociado
    protected static ?string $model = Carrera::class;

    // 🏷️ Etiquetas visibles en el panel
    protected static ?string $modelLabel = 'Carrera';
    protected static ?string $pluralModelLabel = 'Carreras';

    // 🧭 Ícono de menú (versión compatible con Filament 4)
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    // 🔤 Campo que se mostrará como título del registro
    protected static ?string $recordTitleAttribute = 'carrera_nombre';

    //sidebar
    protected static string|\UnitEnum|null $navigationGroup = 'Gestión Académica';
    protected static ?int $navigationSort = 20; // 2do


    // ⚙️ Formulario (usa CarreraForm)
    public static function form(Schema $schema): Schema
    {
        return CarreraForm::configure($schema);
    }

    // 📋 Tabla (usa CarrerasTable)
    public static function table(Table $table): Table
    {
        return CarrerasTable::configure($table);
    }

    // 🔗 Relaciones (por ahora vacío)
    public static function getRelations(): array
    {
        return [];
    }

    // 📄 Páginas del CRUD
    public static function getPages(): array
    {
        return [
            'index'  => ListCarreras::route('/'),
            'create' => CreateCarrera::route('/create'),
            'edit'   => EditCarrera::route('/{record}/edit'),
        ];
    }
}
