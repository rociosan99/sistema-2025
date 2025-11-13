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
    protected static ?string $model = Materia::class;

    protected static ?string $modelLabel = 'Materia';
    protected static ?string $pluralModelLabel = 'Materias';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    // 👈 ESTA ES LA FIRMA correcta en tu proyecto (igual que CarreraResource)
    protected static string|\UnitEnum|null $navigationGroup = 'Gestión Académica';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return MateriaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MateriasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterias::route('/'),
            'create' => CreateMateria::route('/create'),
            'edit' => EditMateria::route('/{record}/edit'),
        ];
    }
}
