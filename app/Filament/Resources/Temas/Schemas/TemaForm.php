<?php

namespace App\Filament\Resources\Temas\Schemas;

use App\Models\Tema;
use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class TemaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('tema_nombre')
                ->label('Nombre del tema')
                ->required()
                ->maxLength(150),

            Forms\Components\Textarea::make('tema_descripcion')
                ->label('Descripción')
                ->rows(3)
                ->nullable()
                ->columnSpanFull(),

            // Select del tema padre (auto-referenciado)
            Forms\Components\Select::make('tema_id_tema_padre')
                ->label('Tema padre')
                ->native(false)
                ->searchable()
                ->placeholder('Sin padre')
                ->options(function (?Model $record) {
                    // Evitar que se pueda elegir a sí mismo como padre al editar
                    return Tema::query()
                        ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                        ->orderBy('tema_nombre')
                        ->pluck('tema_nombre', 'tema_id');
                })
                ->nullable(),
        ]);
    }
}
