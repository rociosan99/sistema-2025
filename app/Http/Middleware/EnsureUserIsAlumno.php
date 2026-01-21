<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAlumno
{
    public function handle(Request $request, Closure $next): Response
    {
        // No autenticado → login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Autenticado pero no alumno → redirigir
        if (Auth::user()->role !== 'alumno') {
            return match (Auth::user()->role) {
                'administrador' => redirect('/admin'),
                'profesor'      => redirect('/profesor'),
                default         => redirect()->route('login'),
            };
        }

        return $next($request);
    }
}
