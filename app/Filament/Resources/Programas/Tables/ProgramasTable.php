<?php

namespace App\Filament\Resources\Programas\Tables;

use App\Models\Carrera;
use App\Models\Institucion;
use App\Models\PlanEstudio;
use App\Models\Programa;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class ProgramasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'programas-admin-table'])
            ->header(view('filament.admin.resources.programas.table-filters-styles'))
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->searchPlaceholder('Buscar por materia o tema')
            ->columns([

                Tables\Columns\TextColumn::make('plan.carrera.institucion.institucion_nombre')
                    ->label('Institución')
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('plan.carrera.carrera_nombre')
                    ->label('Carrera')
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('plan.plan_anio')
                    ->label('Plan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('materia.materia_nombre')
                    ->label('Materia')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('programa_anio')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('temas_list')
                    ->label('Temas')
                    ->getStateUsing(fn ($record) =>
                        $record->temas->pluck('tema_nombre')->join(', ')
                    )
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('temas', fn (Builder $query): Builder => $query
                            ->where('tema_nombre', 'like', "%{$search}%")))
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->filters([
                Filter::make('programa')
                    ->label('Programa')
                    ->form([
                        Select::make('institucion_id')
                            ->label('Institución')
                            ->options(fn (): array => Institucion::query()
                                ->orderBy('institucion_nombre')
                                ->pluck('institucion_nombre', 'institucion_id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('carrera_id', null);
                                $set('plan_id', null);
                            }),

                        Select::make('carrera_id')
                            ->label('Carrera')
                            ->options(fn (Get $get): array => Carrera::query()
                                ->when(
                                    $get('institucion_id'),
                                    fn (Builder $query, int|string $institucionId): Builder => $query
                                        ->where('carrera_institucion_id', $institucionId),
                                )
                                ->orderBy('carrera_nombre')
                                ->pluck('carrera_nombre', 'carrera_id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('plan_id', null)),

                        Select::make('plan_id')
                            ->label('Plan')
                            ->options(fn (Get $get): array => PlanEstudio::query()
                                ->when(
                                    $get('carrera_id'),
                                    fn (Builder $query, int|string $carreraId): Builder => $query
                                        ->where('plan_carrera_id', $carreraId),
                                )
                                ->orderByDesc('plan_anio')
                                ->pluck('plan_anio', 'plan_id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Select::make('anio')
                            ->label('Año')
                            ->options(fn (): array => Programa::query()
                                ->whereNotNull('programa_anio')
                                ->select('programa_anio')
                                ->distinct()
                                ->orderByDesc('programa_anio')
                                ->pluck('programa_anio', 'programa_anio')
                                ->all())
                            ->native(false),
                    ])
                    ->columns(4)
                    ->columnSpan(4)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['institucion_id'] ?? null,
                            fn (Builder $query, int|string $institucionId): Builder => $query
                                ->whereHas('plan.carrera', fn (Builder $query): Builder => $query
                                    ->where('carrera_institucion_id', $institucionId)),
                        )
                        ->when(
                            $data['carrera_id'] ?? null,
                            fn (Builder $query, int|string $carreraId): Builder => $query
                                ->whereHas('plan', fn (Builder $query): Builder => $query
                                    ->where('plan_carrera_id', $carreraId)),
                        )
                        ->when(
                            $data['plan_id'] ?? null,
                            fn (Builder $query, int|string $planId): Builder => $query
                                ->where('programa_plan_id', $planId),
                        )
                        ->when(
                            $data['anio'] ?? null,
                            fn (Builder $query, int|string $anio): Builder => $query
                                ->where('programa_anio', $anio),
                        )),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
