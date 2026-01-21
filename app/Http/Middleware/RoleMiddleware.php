<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Nota: Laravel pasa los parámetros extra del middleware
     * después de $next. Aquí recibimos una cadena como 'admin,profesor'
     * y la parseamos.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle(Request $request, Closure $next, string $role): Response
{
    // Si no hay usuario autenticado, redirigir al login
    if (! $request->user()) {
        return redirect()->route('login');
    }

    // Compara el rol del usuario
    if ($request->user()->role !== $role) {
        abort(403, 'No autorizado');
    }

    return $next($request);
}

}