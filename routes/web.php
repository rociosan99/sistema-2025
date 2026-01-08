<?php

use App\Http\Controllers\ProfileController;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Página inicial (pública)
Route::get('/', function () {
    return view('welcome');
})->name('home');

// 🔴 RUTA SOLO PARA PROBAR MAILTRAP (temporal)
Route::get('/test-mail', function () {
    Mail::to('alumno@test.com')->send(new TestMail());
    return 'Mail enviado correctamente (Mailtrap)';
});

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

// Filament maneja sus propias rutas (/admin, /profesor, /alumno)
require __DIR__.'/auth.php';
