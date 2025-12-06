<?php

namespace App\Filament\Profesor\Pages;

use App\Models\Materia;
use App\Models\Tema;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AreaConocimiento extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Mi área de conocimiento';
    protected static ?string $title = 'Mi área de conocimiento';
    protected static ?string $slug = 'area-conocimiento';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.profesor.pages.area-conocimiento';

    public array $data = [];

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();

        // Materias + precio por HORA desde la pivot profesor_materia
        $materias = $user->materias()
            ->get()
            ->map(function (Materia $materia) {
                return [
                    'materia_id'      => $materia->materia_id,
                    'precio_por_hora' => $materia->pivot->precio_por_hora,
                ];
            })
            ->toArray();

        // Temas actuales
        $temas = $user->temas()
            ->pluck('temas.tema_id')
            ->toArray();

        $this->form->fill([
            'materias' => $materias,
            'temas'    => $temas,
        ]);
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            // 🔹 Materias + precio POR HORA
            Repeater::make('materias')
                ->label('Materias que puedo enseñar')
                ->columns(2)
                ->schema([
                    Select::make('materia_id')
                        ->label('Materia')
                        ->options(Materia::pluck('materia_nombre', 'materia_id'))
                        ->searchable()
                        ->required(),

                    TextInput::make('precio_por_hora')
                        ->label('Precio por hora')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('$')
                        ->required(),
                ])
                ->addActionLabel('Agregar materia')
                ->reorderable(),

            // 🔹 Temas (heredan el precio de la materia)
            Select::make('temas')
                ->label('Temas que domino')
                ->multiple()
                ->options(Tema::pluck('tema_nombre', 'tema_id'))
                ->searchable(),
        ];
    }

    public function save(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $state = $this->form->getState();

        // Materias + precio por HORA en profesor_materia
        $materiasInput = $state['materias'] ?? [];

        $syncMaterias = [];

        foreach ($materiasInput as $item) {
            if (!empty($item['materia_id'])) {
                $syncMaterias[$item['materia_id']] = [
                    'precio_por_hora' => $item['precio_por_hora'] !== null
                        ? (float) $item['precio_por_hora']
                        : null,
                ];
            }
        }

        $user->materias()->sync($syncMaterias);

        // Temas
        $user->temas()->sync($state['temas'] ?? []);

        Notification::make()
            ->title('Información actualizada')
            ->body('Tus materias, temas y precios por hora fueron guardados correctamente.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('Guardar')
                ->color('primary')
                ->action('save'),
        ];
    }
}
