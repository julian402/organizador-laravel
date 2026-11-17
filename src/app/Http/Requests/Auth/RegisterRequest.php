<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Auth\Concerns\ValidaContrasena;
use Illuminate\Foundation\Http\FormRequest;

/**
 * HU-01: validaciones del registro de usuarios (CA-01.1 a CA-01.5).
 */
class RegisterRequest extends FormRequest
{
    use ValidaContrasena;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * CA-01.3: el correo se compara en minúsculas para detectar duplicados como ANA@test.local.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc,strict', 'max:255', 'unique:users,email'],
            ...$this->reglasContrasena(),
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.unique' => 'Ya existe una cuenta registrada con este correo electrónico.',
            ...$this->mensajesContrasena(),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'password' => 'contraseña',
            'password_confirmation' => 'confirmación de contraseña',
        ];
    }
}
