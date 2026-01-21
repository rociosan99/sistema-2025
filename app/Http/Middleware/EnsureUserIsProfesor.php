<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsProfesor
{
    public function handle(Request $request, Closure $next): Response
    {
        // No autenticado → login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Autenticado pero no profesor → redirigir según rol
        if (Auth::user()->role !== 'profesor') {
            return match (Auth::user()->role) {
                'administrador' => redirect('/admin'),
                'alumno'        => redirect('/alumno/dashboard'),
                default         => redirect()->route('login'),
            };
        }

        return $next($request);
    }
}
