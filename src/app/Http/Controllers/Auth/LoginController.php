<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * HU-02: Inicio de sesión. HU-04: Cierre de sesión. HU-05: límite de intentos (en LoginRequest).
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        // CA-02.3 y HU-05: si falla, LoginRequest devuelve al formulario con el mensaje correspondiente.
        $request->authenticate();

        $request->session()->regenerate();

        // CA-02.5: redirección a la página privada.
        return redirect()->intended(route('dashboard'));
    }

    /**
     * HU-04: cierre de sesión.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        // CA-04.2: se invalida la sesión en el servidor (se borra y se crea otra con nuevo ID)
        // y se regenera el token CSRF.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // CA-04.3: redirección al inicio de sesión.
        return redirect()->route('login')->with('status', 'Cerraste sesión correctamente.');
    }
}
