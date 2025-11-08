<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ProfesorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // Identificación del panel
            ->id('profesor')
            ->path('profesor')
            ->brandName('Panel del Profesor')
            ->colors([
                'primary' => Color::Amber, // color temático del panel
            ])

            // Descubrir los recursos del panel
            ->discoverResources(in: app_path('Filament/Profesor/Resources'), for: 'App\Filament\Profesor\Resources')
            ->discoverPages(in: app_path('Filament/Profesor/Pages'), for: 'App\Filament\Profesor\Pages')
            ->discoverWidgets(in: app_path('Filament/Profesor/Widgets'), for: 'App\Filament\Profesor\Widgets')

            // Página principal
            ->pages([
                Dashboard::class,
            ])

            // Widgets del dashboard
            ->widgets([
                AccountWidget::class,
            ])

            // Middlewares necesarios para Filament
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            // Middleware de autenticación y rol
            ->authMiddleware([
                Authenticate::class, // Autenticación Filament
                'role:profesor',     // 🔒 Solo usuarios con rol profesor
            ]);
    }
}
