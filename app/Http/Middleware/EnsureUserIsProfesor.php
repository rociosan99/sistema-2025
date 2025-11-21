<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsProfesor
{
    /**
     * Maneja una solicitud entrante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si no está autenticado → ir al login general (Breeze)
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Si el usuario NO es profesor, lo redirigimos según su rol
        if ($user->role !== 'profesor') {
            $redirectPath = match ($user->role) {
                'administrador' => '/admin',              // Panel de admin
                'alumno'        => '/alumno/dashboard',    // Futuro dashboard alumno
                default         => '/login',              // Desconocido → login
            };

            return redirect($redirectPath);
        }

        // Si es profesor, puede continuar al panel /profesor
        return $next($request);
    }
}
