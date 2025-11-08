<?php

namespace App\Filament\Resources\Materias;

use App\Filament\Resources\Materias\Pages\CreateMateria;
use App\Filament\Resources\Materias\Pages\EditMateria;
use App\Filament\Resources\Materias\Pages\ListMaterias;
use App\Filament\Resources\Materias\Schemas\MateriaForm;
use App\Filament\Resources\Materias\Tables\MateriasTable;
use App\Models\Materia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MateriaResource extends Resource
{
    // 📦 Modelo asociado
    protected static ?string $model = Materia::class;

    // 🏷️ Nombres visibles en el panel
    protected static ?string $modelLabel = 'Materia';
    protected static ?string $pluralModelLabel = 'Materias';

    // 🧭 Ícono en el menú (compatible con Filament v4)
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    // 🔤 Campo que se usa como título del registro
    protected static ?string $recordTitleAttribute = 'materia_nombre';

    //sidebar
    protected static string|\UnitEnum|null $navigationGroup = 'Gestión Académica';
    protected static ?int $navigationSort = 30; // 3ro


    // ⚙️ Formulario (usa MateriaForm.php)
    public static function form(Schema $schema): Schema
    {
        return MateriaForm::configure($schema);
    }

    // 📋 Tabla (usa MateriasTable.php)
    public static function table(Table $table): Table
    {
        return MateriasTable::configure($table);
    }

    // 🔗 Relaciones (por ahora no hay)
    public static function getRelations(): array
    {
        return [];
    }

    // 📄 Páginas del CRUD
    public static function getPages(): array
    {
        return [
            'index' => ListMaterias::route('/'),
            'create' => CreateMateria::route('/create'),
            'edit' => EditMateria::route('/{record}/edit'),
        ];
    }
}
