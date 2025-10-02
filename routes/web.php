<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


// Página inicial (pública)
Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', fn() => view('admin.dashboard'))->name('admin.dashboard');
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
