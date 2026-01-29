<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TurnoConfirmacionController;
use App\Http\Controllers\MercadoPagoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Home
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Perfil (Breeze)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Confirmación de asistencia (mail 24h) - LINKS FIRMADOS
|--------------------------------------------------------------------------
*/
Route::get('/turnos/{turno}/confirmar-asistencia', [TurnoConfirmacionController::class, 'confirmar'])
    ->name('turnos.confirmar-asistencia')
    ->middleware('signed');

Route::get('/turnos/{turno}/cancelar-asistencia', [TurnoConfirmacionController::class, 'cancelar'])
    ->name('turnos.cancelar-asistencia')
    ->middleware('signed');

/*
|--------------------------------------------------------------------------
| Mercado Pago (Checkout Pro)
|--------------------------------------------------------------------------
| mp.pagar        => botón en panel (logueado)
| mp.pagar.mail   => link por mail (firmado + alumno_id) (NO requiere login)
|--------------------------------------------------------------------------
*/
Route::get('/turnos/{turno}/pagar', [MercadoPagoController::class, 'pagar'])
    ->name('mp.pagar');

Route::get('/turnos/{turno}/pagar-mail', [MercadoPagoController::class, 'pagarDesdeMail'])
    ->name('mp.pagar.mail')
    ->middleware('signed');

// ✅ NUEVO: link de pago para el mail (firmado)
Route::get('/turnos/{turno}/pagar-mail', [MercadoPagoController::class, 'pagarDesdeMail'])
    ->name('mp.pagar.mail')
    ->middleware('signed');

// Back URLs (vuelve el usuario)
Route::get('/mp/success/{turno}', [MercadoPagoController::class, 'success'])->name('mp.success');
Route::get('/mp/failure/{turno}', [MercadoPagoController::class, 'failure'])->name('mp.failure');
Route::get('/mp/pending/{turno}', [MercadoPagoController::class, 'pending'])->name('mp.pending');

// Webhook (MP llama desde afuera)
Route::post('/webhooks/mercadopago', [MercadoPagoController::class, 'webhook'])
    ->name('mp.webhook');

require __DIR__ . '/auth.php';
