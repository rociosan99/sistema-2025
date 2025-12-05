<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsProfesor;
use App\Http\Middleware\EnsureUserIsAlumno;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

       
        $middleware->alias([
            'admin'    => EnsureUserIsAdmin::class,
            'profesor' => EnsureUserIsProfesor::class,
            'alumno'   => EnsureUserIsAlumno::class,
            'role'     => RoleMiddleware::class,
    
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
