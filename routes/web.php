<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Página inicial (pública)
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Cierre de sesión forzado (opcional)
Route::get('/force-logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('force.logout');

// Perfil de usuario (accesible para cualquier usuario autenticado)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ✅ Ya no definimos dashboard de profesor/alumno aquí.
// Filament crea sus propias rutas bajo /profesor o /admin automáticamente.

require __DIR__.'/auth.php';
