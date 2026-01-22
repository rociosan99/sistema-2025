<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TurnoConfirmacionController;
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

/*
|--------------------------------------------------------------------------
| Confirmación de asistencia (links firmados del mail 24h)
|--------------------------------------------------------------------------
| - middleware('signed') impide que inventen el link
| - NO lleva auth, así funciona desde el mail aunque no esté logueado
*/
Route::get('/turnos/{turno}/confirmar-asistencia', [TurnoConfirmacionController::class, 'confirmar'])
    ->name('turnos.confirmar-asistencia')
    ->middleware('signed');

Route::get('/turnos/{turno}/cancelar-asistencia', [TurnoConfirmacionController::class, 'cancelar'])
    ->name('turnos.cancelar-asistencia')
    ->middleware('signed');

// Breeze routes (register/login/logout/etc)
require __DIR__ . '/auth.php';
