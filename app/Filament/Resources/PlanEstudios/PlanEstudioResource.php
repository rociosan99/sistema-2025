<?php

namespace App\Filament\Resources\PlanEstudios;

use App\Filament\Resources\PlanEstudios\Pages\ListPlanEstudios;
use App\Filament\Resources\PlanEstudios\Pages\CreatePlanEstudio;
use App\Filament\Resources\PlanEstudios\Pages\EditPlanEstudio;

use App\Filament\Resources\PlanEstudios\Schemas\PlanEstudioForm;
use App\Filament\Resources\PlanEstudios\Tables\PlanEstudiosTable;

use App\Models\PlanEstudio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PlanEstudioResource extends Resource
{
    protected static ?string $model = PlanEstudio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Plan de Estudio';
    protected static ?string $pluralModelLabel = 'Planes de Estudio';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión Académica';
    protected static ?int $navigationSort = 25;

    public static function form(Schema $schema): Schema
    {
        return PlanEstudioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanEstudiosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPlanEstudios::route('/'),
            'create' => CreatePlanEstudio::route('/create'),
            'edit'   => EditPlanEstudio::route('/{record}/edit'),
        ];
    }
}
