<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * HU-03: solicitar el enlace de recuperación de contraseña.
 */
class ForgotPasswordController extends Controller
{
    public const MENSAJE_GENERICO = 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña. '
        .'Revisa tu bandeja de entrada.';

    /** CA-03.1: formulario para solicitar la recuperación. */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        // CA-03.3: si la cuenta existe, Laravel genera un token aleatorio, guarda solo su hash
        // en password_reset_tokens y envía el enlace (User::sendPasswordResetNotification).
        Password::sendResetLink($request->only('email'));

        // CA-03.2: se ignora el resultado a propósito; la respuesta es idéntica exista o no la cuenta.
        return back()->with('status', self::MENSAJE_GENERICO);
    }
}
