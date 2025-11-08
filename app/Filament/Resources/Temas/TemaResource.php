<?php

namespace App\Filament\Resources\Temas;

use App\Filament\Resources\Temas\Pages\CreateTema;
use App\Filament\Resources\Temas\Pages\EditTema;
use App\Filament\Resources\Temas\Pages\ListTemas;
use App\Filament\Resources\Temas\Schemas\TemaForm;
use App\Filament\Resources\Temas\Tables\TemasTable;
use App\Models\Tema;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TemaResource extends Resource
{
    // 🔗 Modelo asociado
    protected static ?string $model = Tema::class;

    // 🏷️ Etiquetas para el panel
    protected static ?string $modelLabel = 'Tema';
    protected static ?string $pluralModelLabel = 'Temas';

    // 🧭 Ícono del menú (compatible con Filament 4)
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    // 🔤 Campo que se muestra como título del registro
    protected static ?string $recordTitleAttribute = 'tema_nombre';

    //sidebar
    protected static string|\UnitEnum|null $navigationGroup = 'Gestión Académica';
    protected static ?int $navigationSort = 40; // 4to


    // ⚙️ Formulario (usa tu TemaForm.php)
    public static function form(Schema $schema): Schema
    {
        return TemaForm::configure($schema);
    }

    // 📋 Tabla (usa tu TemasTable.php)
    public static function table(Table $table): Table
    {
        return TemasTable::configure($table);
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
            'index' => ListTemas::route('/'),
            'create' => CreateTema::route('/create'),
            'edit' => EditTema::route('/{record}/edit'),
        ];
    }
}
