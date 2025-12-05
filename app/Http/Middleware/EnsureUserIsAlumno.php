<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAlumno
{
    /**
     * Maneja una solicitud entrante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si no está logueado → al login general
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Si está logueado pero NO es alumno → 403
        if (Auth::user()->role !== 'alumno') {
            abort(403, 'Acceso denegado. Solo los alumnos pueden ingresar a este panel.');
        }

        // Todo OK, sigue la request
        return $next($request);
    }
}
