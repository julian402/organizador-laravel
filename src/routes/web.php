<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'))->name('home');

// "Hola mundo" para verificar el despliegue (local y luego en AWS).
Route::get('/salud', fn () => response()->json([
    'app' => config('app.name'),
    'mensaje' => 'Hola mundo',
    'estado' => 'ok',
]))->name('salud');

Route::middleware('guest')->group(function () {
    // HU-01
    Route::get('/registro', [RegisterController::class, 'create'])->name('register');
    Route::post('/registro', [RegisterController::class, 'store'])->middleware('throttle:registro');

    // HU-02 (el límite de intentos fallidos de HU-05 está en LoginRequest)
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');

    // HU-03
    Route::get('/recuperar', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/recuperar', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:recuperar')
        ->name('password.email');
    Route::get('/restablecer/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/restablecer', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:restablecer')
        ->name('password.update');
});

// CA-02.6: el middleware 'auth' redirige al login a los visitantes no autenticados.
// CA-04.4: 'sin-cache' evita que el botón Atrás muestre páginas privadas después de cerrar sesión.
Route::middleware(['auth', 'sin-cache'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // HU-04 (solo POST con token CSRF: un enlace externo no puede cerrar la sesión del usuario)
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
