<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Mostrar la vista de login de Breeze.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesar la solicitud de autenticación.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        // 🔐 Redirecciones según rol
        return match ($user->role) {
            // 👉 Panel Filament de administrador
            'admin' => redirect('/admin'),

            // 👉 Panel Filament de profesor
            'profesor' => redirect('/profesor'),

            // 👉 Dashboard manual del alumno (todavía Blade)
            'alumno' => redirect('/alumno/dashboard'),

            // 👉 Rol desconocido
            default => redirect('/login'),
        };
    }

    /**
     * Cerrar sesión.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
