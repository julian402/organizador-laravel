<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * HU-02: Inicio de sesión.
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            // CA-02.3: mensaje genérico, no revela si falló el correo o la contraseña.
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Las credenciales ingresadas no son válidas.']);
        }

        $request->session()->regenerate();

        // CA-02.5: redirección a la página privada.
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Cierre de sesión básico (se completa en HU-04).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
