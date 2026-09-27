<?php

namespace App\Filament\Resources\PlanEstudios\Tables;

use App\Models\Carrera;
use App\Models\Institucion;
use App\Models\PlanEstudio;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class PlanEstudiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'planes-estudio-admin-table'])
            ->header(view('filament.admin.resources.planes-estudio.table-filters-styles'))
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->searchPlaceholder('Buscar por carrera o descripción')
            ->columns([
                Tables\Columns\TextColumn::make('plan_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('carrera.carrera_nombre')
                    ->label('Carrera')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('plan_anio')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan_descripcion')
                    ->searchable()
                    ->label('Descripción')
                    ->limit(60),
            ])
            ->filters([
                Filter::make('plan')
                    ->label('Plan de estudio')
                    ->form([
                        Select::make('institucion_id')
                            ->label('Institución')
                            ->searchable()
                            ->searchPrompt('Buscar institución...')
                            ->getSearchResultsUsing(fn (string $search): array => Institucion::query()
                                ->where('institucion_nombre', 'like', "%{$search}%")
                                ->orderBy('institucion_nombre')
                                ->limit(50)
                                ->pluck('institucion_nombre', 'institucion_id')
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => Institucion::query()
                                ->whereKey($value)
                                ->value('institucion_nombre'))
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('carrera_id', null)),

                        Select::make('carrera_id')
                            ->label('Carrera')
                            ->searchable()
                            ->searchPrompt('Buscar carrera...')
                            ->getSearchResultsUsing(fn (Get $get, string $search): array => Carrera::query()
                                ->when(
                                    $get('institucion_id'),
                                    fn (Builder $query, int|string $institucionId): Builder => $query
                                        ->where('carrera_institucion_id', $institucionId),
                                )
                                ->where('carrera_nombre', 'like', "%{$search}%")
                                ->orderBy('carrera_nombre')
                                ->limit(50)
                                ->pluck('carrera_nombre', 'carrera_id')
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => Carrera::query()
                                ->whereKey($value)
                                ->value('carrera_nombre'))
                            ->native(false),

                        Select::make('anio')
                            ->label('Año')
                            ->options(fn (): array => PlanEstudio::query()
                                ->select('plan_anio')
                                ->distinct()
                                ->orderByDesc('plan_anio')
                                ->pluck('plan_anio', 'plan_anio')
                                ->all())
                            ->native(false),
                    ])
                    ->columns(3)
                    ->columnSpan(3)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['institucion_id'] ?? null,
                            fn (Builder $query, int|string $institucionId): Builder => $query
                                ->whereHas('carrera', fn (Builder $query): Builder => $query
                                    ->where('carrera_institucion_id', $institucionId)),
                        )
                        ->when(
                            $data['carrera_id'] ?? null,
                            fn (Builder $query, int|string $carreraId): Builder => $query
                                ->where('plan_carrera_id', $carreraId),
                        )
                        ->when(
                            $data['anio'] ?? null,
                            fn (Builder $query, int|string $anio): Builder => $query
                                ->where('plan_anio', $anio),
                        )),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
