<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * HU-02: validaciones del inicio de sesión (CA-02.1 y CA-02.4).
 * HU-05: límite de intentos fallidos por cuenta + IP (CA-05.1 a CA-05.5).
 */
class LoginRequest extends FormRequest
{
    /** CA-05.1: intentos fallidos consecutivos permitidos. */
    public const MAX_INTENTOS = 5;

    /** CA-05.1: ventana en la que se cuentan los intentos fallidos. */
    public const MINUTOS_VENTANA = 15;

    /** CA-05.2: duración del bloqueo al alcanzar el límite. */
    public const MINUTOS_BLOQUEO = 15;

    /** CA-02.3: mismo mensaje si falla el correo o la contraseña. */
    public const MENSAJE_CREDENCIALES = 'Las credenciales ingresadas no son válidas.';

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
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => 'correo electrónico',
            'password' => 'contraseña',
        ];
    }

    /**
     * Intenta autenticar aplicando el control de intentos fallidos de HU-05.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        // CA-05.2: mientras dure el bloqueo no se verifica la contraseña, aunque sea correcta.
        $this->asegurarQueNoEstaBloqueado();

        if (! Auth::attempt($this->only('email', 'password'))) {
            $this->registrarIntentoFallido();

            throw ValidationException::withMessages(['email' => self::MENSAJE_CREDENCIALES]);
        }

        // CA-05.5: un inicio de sesión exitoso reinicia el contador.
        RateLimiter::clear($this->claveIntentos());
    }

    private function asegurarQueNoEstaBloqueado(): void
    {
        if (RateLimiter::tooManyAttempts($this->claveBloqueo(), 1)) {
            $this->lanzarBloqueo();
        }
    }

    private function registrarIntentoFallido(): void
    {
        // CA-05.1: los fallos se cuentan durante una ventana de 15 minutos.
        $fallos = RateLimiter::hit($this->claveIntentos(), self::MINUTOS_VENTANA * 60);

        if ($fallos < self::MAX_INTENTOS) {
            return;
        }

        // CA-05.2: al llegar al límite se bloquea durante 15 minutos contados desde ahora.
        RateLimiter::clear($this->claveIntentos());
        RateLimiter::hit($this->claveBloqueo(), self::MINUTOS_BLOQUEO * 60);

        // Registro de auditoría sin datos sensibles (no se guarda el correo ni la contraseña).
        Log::warning('Inicio de sesión bloqueado por intentos fallidos', ['ip' => $this->ip()]);

        $this->lanzarBloqueo();
    }

    /**
     * CA-05.3: mensaje comprensible que no revela si la cuenta existe
     * (el contador es por correo escrito + IP, exista o no la cuenta).
     */
    private function lanzarBloqueo(): never
    {
        $minutos = max(1, (int) ceil(RateLimiter::availableIn($this->claveBloqueo()) / 60));

        throw ValidationException::withMessages([
            'email' => 'Superaste el número de intentos permitidos. Por seguridad, el inicio de sesión '
                ."quedó bloqueado temporalmente. Inténtalo de nuevo en {$minutos} ".($minutos === 1 ? 'minuto.' : 'minutos.'),
        ]);
    }

    /**
     * CA-05.1: clave por combinación de cuenta e IP. Se guarda como hash para no dejar correos en la caché.
     */
    private function claveIntentos(): string
    {
        return 'login-fallos:'.hash('sha256', $this->string('email')->lower().'|'.$this->ip());
    }

    private function claveBloqueo(): string
    {
        return 'login-bloqueo:'.hash('sha256', $this->string('email')->lower().'|'.$this->ip());
    }
}
