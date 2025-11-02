<?php

namespace App\Providers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * 🔁 Define la redirección por defecto después de iniciar sesión.
         * Equivale a RouteServiceProvider::HOME en versiones anteriores.
         */
        if (!app()->runningInConsole()) {
            // Redirige a /admin si el usuario es administrador
            Redirect::macro('home', function () {
                $user = Auth::user();

                if ($user) {
                    return match ($user->role) {
                        'administrador' => '/admin',
                        'profesor' => '/profesor/dashboard',
                        'alumno' => '/alumno/dashboard',
                        default => '/',
                    };
                }

                return '/';
            });
        }
    }
}
