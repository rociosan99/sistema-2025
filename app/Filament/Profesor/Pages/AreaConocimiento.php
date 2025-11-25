<?php

namespace App\Filament\Profesor\Pages;

use App\Models\Materia;
use App\Models\Tema;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
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
        $user = Auth::user();

        $this->form->fill([
            'materias' => $user->materias()->pluck('materias.materia_id')->toArray(),
            'temas'    => $user->temas()->pluck('temas.tema_id')->toArray(),
        ]);
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('materias')
                ->label('Materias que puedo enseñar')
                ->multiple()
                ->options(Materia::pluck('materia_nombre', 'materia_id'))
                ->searchable(),

            Select::make('temas')
                ->label('Temas que domino')
                ->multiple()
                ->options(Tema::pluck('tema_nombre', 'tema_id'))
                ->searchable(),
        ];
    }

    public function save(): void
    {
        $user = Auth::user();
        $state = $this->form->getState();

        $user->materias()->sync($state['materias'] ?? []);
        $user->temas()->sync($state['temas'] ?? []);

        Notification::make()
            ->title('Información actualizada')
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
