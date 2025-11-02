<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; 
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Maneja una solicitud entrante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si no hay usuario autenticado → redirige al login
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Si está autenticado pero no es administrador → error 403
        if (Auth::user()->role !== 'administrador') {
            abort(403, 'Acceso denegado. Solo los administradores pueden ingresar al panel.');
        }

        // Si pasa ambas condiciones, continúa
        return $next($request);
    }
}
