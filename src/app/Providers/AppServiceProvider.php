<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Límites por IP contra abuso automatizado. Son una capa extra: el bloqueo por
     * cuenta + IP que exige HU-05 está en App\Http\Requests\Auth\LoginRequest.
     */
    public function boot(): void
    {
        // Evita probar muchos correos distintos desde la misma IP.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(20)
            ->by($request->ip())
            ->response(fn () => $this->demasiadosIntentos($request)));

        // Contra creación masiva de cuentas.
        RateLimiter::for('registro', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn () => $this->demasiadosIntentos($request)));

        // HU-03: contra el envío masivo de correos de recuperación.
        RateLimiter::for('recuperar', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn () => $this->demasiadosIntentos($request)));

        // HU-03: contra pruebas masivas de tokens.
        RateLimiter::for('restablecer', fn (Request $request) => Limit::perMinute(10)
            ->by($request->ip())
            ->response(fn () => $this->demasiadosIntentos($request)));
    }

    private function demasiadosIntentos(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->only('name', 'email'))
            ->withErrors(['email' => 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.']);
    }
}
