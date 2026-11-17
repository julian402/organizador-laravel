<?php

namespace App\Http\Requests\Auth\Concerns;

use Illuminate\Validation\Rules\Password;

/**
 * Reglas de contraseña compartidas por el registro (HU-01, CA-01.4 y CA-01.5)
 * y el restablecimiento de contraseña (HU-03, CA-03.5): un solo lugar para que no se desalineen.
 */
trait ValidaContrasena
{
    protected function reglasContrasena(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'password_confirmation' => ['required'],
        ];
    }

    protected function mensajesContrasena(): array
    {
        return [
            'password.confirmed' => 'La contraseña y su confirmación no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe tener al menos una letra mayúscula y una minúscula.',
            'password.numbers' => 'La contraseña debe tener al menos un número.',
        ];
    }
}
