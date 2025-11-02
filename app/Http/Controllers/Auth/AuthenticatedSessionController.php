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
            'administrador' => redirect('/admin'), // 👉 ahora dirige al panel Filament
            'profesor' => redirect()->route('profesor.dashboard'),
            'alumno' => redirect()->route('alumno.dashboard'),
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
