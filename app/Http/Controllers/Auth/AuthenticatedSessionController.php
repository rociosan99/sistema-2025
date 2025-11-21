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
        // Autenticar credenciales
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        // 🔐 Redirección correcta según el rol REAL de tu BD
        $redirectPath = match ($user->role) {
            'administrador' => '/admin',            // Panel Filament admin
            'profesor'      => '/profesor',         // Panel Filament profesor
            'alumno'        => '/alumno/dashboard', // Blade del alumno, si lo creás
            default         => '/login',
        };

        // 🔁 Usa intended() si venía de página protegida
        return redirect()->intended($redirectPath);
    }

    /**
     * Cerrar sesión.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
