<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
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

    // HU-02
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

// CA-02.6: el middleware 'auth' redirige al login a los visitantes no autenticados.
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
