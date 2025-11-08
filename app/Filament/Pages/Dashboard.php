<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Panel de Control';
    protected static ?string $slug = 'dashboard';
    protected static string|\UnitEnum|null $navigationGroup = 'General';
    
    protected static bool $shouldShowHeader = false;

    protected string $view = 'filament.pages.dashboard';
}