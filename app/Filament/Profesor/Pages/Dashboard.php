<?php

namespace App\Filament\Profesor\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Panel del Profesor';
    protected static ?string $slug = 'dashboard';

    protected static ?int $navigationSort = -1; // 👈 PRIMERO EN EL MENÚ

    protected string $view = 'filament.profesor.pages.dashboard';

    public array $materias = [];
    public array $temas = [];

    public function mount(): void
    {
        $user = Auth::user();

        $this->materias = $user->materias()
            ->orderBy('materia_nombre')
            ->pluck('materia_nombre')
            ->toArray();

        $this->temas = $user->temas()
            ->orderBy('tema_nombre')
            ->pluck('tema_nombre')
            ->toArray();
    }
}
