<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // No autenticado → login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Autenticado pero no admin → redirigir a su panel
        if (Auth::user()->role !== 'administrador') {
            return match (Auth::user()->role) {
                'profesor' => redirect('/profesor'),
                'alumno'   => redirect('/alumno/dashboard'),
                default    => redirect()->route('login'),
            };
        }

        return $next($request);
    }
}
