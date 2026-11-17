<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Auth\Concerns\ValidaContrasena;
use Illuminate\Foundation\Http\FormRequest;

/**
 * HU-03: establecer la nueva contraseña (CA-03.5: mismas reglas que HU-01).
 */
class ResetPasswordRequest extends FormRequest
{
    use ValidaContrasena;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc,strict', 'max:255'],
            ...$this->reglasContrasena(),
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            ...$this->mensajesContrasena(),
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'enlace de recuperación',
            'email' => 'correo electrónico',
            'password' => 'nueva contraseña',
            'password_confirmation' => 'confirmación de contraseña',
        ];
    }
}
