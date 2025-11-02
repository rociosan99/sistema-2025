<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Página inicial (pública)
Route::get('/', function () {
    return view('welcome');
});

Route::get('/force-logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
});


Route::middleware(['auth', 'role:profesor'])->group(function () {
    Route::get('/profesor/dashboard', fn() => view('profesor.dashboard'))->name('profesor.dashboard');
});

Route::middleware(['auth', 'role:alumno'])->group(function () {
    Route::get('/alumno/dashboard', fn() => view('alumno.dashboard'))->name('alumno.dashboard');
});


// Perfil (accesible para cualquier usuario autenticado)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
