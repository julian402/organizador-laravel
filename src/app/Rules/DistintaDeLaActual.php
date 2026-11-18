<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

/**
 * HU-03 (CA-03.8): la nueva contraseña no puede ser igual a la contraseña actual de la cuenta.
 *
 * Se compara contra el hash guardado (nunca se conoce la contraseña en texto plano).
 * Solo debe usarse cuando ya se comprobó que el enlace de recuperación es válido,
 * para que no sirva como "oráculo" para adivinar la contraseña de otra cuenta.
 */
class DistintaDeLaActual implements ValidationRule
{
    public const MENSAJE = 'La nueva contraseña debe ser diferente a tu contraseña actual.';

    public function __construct(private readonly User $user)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && Hash::check($value, $this->user->getAuthPassword())) {
            $fail(self::MENSAJE);
        }
    }
}
