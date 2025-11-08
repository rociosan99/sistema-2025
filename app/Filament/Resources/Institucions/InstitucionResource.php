<?php

namespace App\Filament\Resources\Institucions;

use App\Filament\Resources\Institucions\Pages\CreateInstitucion;
use App\Filament\Resources\Institucions\Pages\EditInstitucion;
use App\Filament\Resources\Institucions\Pages\ListInstitucions;
use App\Filament\Resources\Institucions\Schemas\InstitucionForm;
use App\Filament\Resources\Institucions\Tables\InstitucionsTable;
use App\Models\Institucion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InstitucionResource extends Resource
{
    // 📦 Modelo
    protected static ?string $model = Institucion::class;

    // 🏷️ Etiquetas en español
    protected static ?string $modelLabel = 'Institución';
    protected static ?string $pluralModelLabel = 'Instituciones';

    // 🧭 Ícono del menú (versión compatible)
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    // 🔤 Campo que se mostrará como título
    protected static ?string $recordTitleAttribute = 'institucion_nombre';

    // 🗂️ Grupo y orden dentro del sidebar
    protected static string|\UnitEnum|null $navigationGroup = 'Gestión Académica';
    protected static ?int $navigationSort = 10; // aparece primero en el grupo

    // ⚙️ Formulario
    public static function form(Schema $schema): Schema
    {
        return InstitucionForm::configure($schema);
    }

    // 📋 Tabla
    public static function table(Table $table): Table
    {
        return InstitucionsTable::configure($table);
    }

    // 🔗 Relaciones
    public static function getRelations(): array
    {
        return [];
    }

    // 📄 Páginas CRUD
    public static function getPages(): array
    {
        return [
            'index' => ListInstitucions::route('/'),
            'create' => CreateInstitucion::route('/create'),
            'edit' => EditInstitucion::route('/{record}/edit'),
        ];
    }
}
