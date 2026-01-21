<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // Si está logueado, mandalo directo a su panel según role
    if (Auth::check()) {
        $role = Auth::user()?->role;

        return match ($role) {
            'admin'    => redirect('/admin'),
            'profesor' => redirect('/profesor'),
            'alumno'   => redirect('/alumno'),
            default    => view('welcome'),
        };
    }

    // No logueado → welcome
    return view('welcome');
});

// Perfil Breeze (opcional, no interfiere con Filament)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Breeze routes (register/login/logout/etc)
require __DIR__ . '/auth.php';
