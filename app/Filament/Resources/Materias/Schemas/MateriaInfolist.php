<?php

namespace App\Filament\Resources\Materias\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MateriaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('nombre'),
                TextEntry::make('descripcion')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('tema_padre')
                    ->placeholder('-'),
                TextEntry::make('tema_hijo')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
