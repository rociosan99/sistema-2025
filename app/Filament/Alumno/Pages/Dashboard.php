<?php

namespace App\Filament\Alumno\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
     protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Panel del Alumno';
    protected static ?string $slug = 'dashboard';

    protected string $view = 'filament.alumno.pages.dashboard';
}
