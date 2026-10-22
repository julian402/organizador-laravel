<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * HU-01: Registro de usuarios.
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        // CA-01.7: el cast 'hashed' del modelo guarda la contraseña con bcrypt.
        User::create($request->only('name', 'email', 'password'));

        // CA-01.6: mensaje de confirmación.
        return redirect()
            ->route('login')
            ->with('status', 'Tu cuenta fue creada correctamente. Ya puedes iniciar sesión.');
    }
}
