<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\TurnoCancelarPanelController;
use App\Http\Controllers\TurnoReemplazoResponderController;
use App\Http\Controllers\Auth\GoogleAuthController; // ✅ GOOGLE
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------|
| Home
|--------------------------------------------------------------------------|
*/
Route::get('/', function () {
    if (Auth::check()) {
        $role = Auth::user()?->role;

        return match ($role) {
            'admin'    => redirect('/admin'),
            'profesor' => redirect('/profesor'),
            'alumno'   => redirect('/alumno'),
            default    => view('welcome'),
        };
    }

    return view('welcome');
});

/*
|--------------------------------------------------------------------------|
| ✅ Google OAuth
|--------------------------------------------------------------------------|
*/
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

/*
|--------------------------------------------------------------------------|
| Perfil (Breeze)
|--------------------------------------------------------------------------|
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------|
| Mercado Pago
|--------------------------------------------------------------------------|
*/
Route::get('/turnos/{turno}/pagar', [MercadoPagoController::class, 'pagar'])
    ->name('mp.pagar');

Route::get('/turnos/{turno}/pagar-mail', [MercadoPagoController::class, 'pagarDesdeMail'])
    ->name('mp.pagar.mail')
    ->middleware('signed');

// Back URLs
Route::get('/mp/success/{turno}', [MercadoPagoController::class, 'success'])->name('mp.success');
Route::get('/mp/failure/{turno}', [MercadoPagoController::class, 'failure'])->name('mp.failure');
Route::get('/mp/pending/{turno}', [MercadoPagoController::class, 'pending'])->name('mp.pending');

// Webhook
Route::post('/webhooks/mercadopago', [MercadoPagoController::class, 'webhook'])
    ->name('mp.webhook');

/*
|--------------------------------------------------------------------------|
| Alumno: Cancelar turno desde el panel (logueado)
|--------------------------------------------------------------------------|
*/
Route::middleware(['auth'])->group(function () {
    Route::post('/turnos/{turno}/cancelar-panel', TurnoCancelarPanelController::class)
        ->name('turnos.cancelar-panel');
});

Route::middleware(['web'])
    ->get('/reemplazos/{turnoReemplazo}/{accion}', TurnoReemplazoResponderController::class)
    ->name('reemplazos.responder')
    ->middleware('signed'); // link firmado

require __DIR__ . '/auth.php';