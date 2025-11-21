<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| MIDDLEWARE personalizados
|--------------------------------------------------------------------------
*/
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsProfesor;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        /*
        |--------------------------------------------------------------------------
        | 🔐 Alias de middlewares personalizados
        |--------------------------------------------------------------------------
        | Podés usarlos en cualquier ruta o panel:
        | ->middleware('admin')
        | ->middleware('profesor')
        | ->middleware('role:administrador')
        */
        $middleware->alias([
            'admin'    => EnsureUserIsAdmin::class,
            'profesor' => EnsureUserIsProfesor::class,
            'role'     => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
