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
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Contra fuerza bruta: 5 intentos por minuto por correo+IP, y 20 por IP
        // (evita probar muchos correos distintos desde la misma IP).
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)
                ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip())
                ->response(fn () => $this->demasiadosIntentos($request)),
            Limit::perMinute(20)
                ->by($request->ip())
                ->response(fn () => $this->demasiadosIntentos($request)),
        ]);

        // Contra creación masiva de cuentas.
        RateLimiter::for('registro', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn () => $this->demasiadosIntentos($request)));
    }

    /**
     * Vuelve al formulario con un mensaje, en vez de la página 429 genérica.
     */
    private function demasiadosIntentos(Request $request): RedirectResponse
    {
        return back()
            ->withInput($request->only('name', 'email'))
            ->withErrors(['email' => 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.']);
    }
}
