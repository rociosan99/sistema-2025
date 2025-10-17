<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MateriaController;

// Página inicial (pública)
Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', fn() => view('admin.dashboard'))->name('admin.dashboard');

     // Gestión de usuarios
    Route::get('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('admin.users.edit');
    Route::delete('/admin/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::put('/admin/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.users.update');

    Route::get('/admin/materias', [\App\Http\Controllers\Admin\MateriaController::class, 'index'])->name('admin.materias.index');
    Route::get('/admin/materias/create', [\App\Http\Controllers\Admin\MateriaController::class, 'create'])->name('admin.materias.create');
    Route::post('/admin/materias', [\App\Http\Controllers\Admin\MateriaController::class, 'store'])->name('admin.materias.store');
    Route::get('/admin/materias/{materia}/edit', [\App\Http\Controllers\Admin\MateriaController::class, 'edit'])->name('admin.materias.edit');
    Route::put('/admin/materias/{materia}', [\App\Http\Controllers\Admin\MateriaController::class, 'update'])->name('admin.materias.update');
    Route::delete('/admin/materias/{materia}', [\App\Http\Controllers\Admin\MateriaController::class, 'destroy'])->name('admin.materias.destroy');
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
