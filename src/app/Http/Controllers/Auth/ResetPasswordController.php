<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Rules\DistintaDeLaActual;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * HU-03: establecer la nueva contraseña desde el enlace recibido por correo.
 */
class ResetPasswordController extends Controller
{
    /** Mismo mensaje para token inválido, vencido, ya usado o correo inexistente. */
    public const MENSAJE_ENLACE_INVALIDO = 'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.';

    public function create(Request $request, string $token): View
    {
        $email = mb_strtolower(trim((string) $request->query('email')));
        $user = $email !== '' ? Password::getUser(['email' => $email]) : null;

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
            // CA-03.4 y CA-03.6: si el enlace ya no sirve se avisa antes de pedir la contraseña.
            'enlaceValido' => $user !== null && Password::tokenExists($user, $token),
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        // Password::reset comprueba que el token exista, coincida con su hash y no tenga más de
        // 30 minutos (CA-03.4). Si todo es correcto, lo borra: queda de un solo uso (CA-03.6).
        $estado = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // CA-03.8: aquí el token ya se validó. Si la contraseña es igual a la actual se lanza
                // ValidationException antes de que el broker borre el token, así el enlace sigue sirviendo.
                Validator::make(
                    ['password' => $password],
                    ['password' => [new DistintaDeLaActual($user)]],
                )->validate();

                // CA-03.7: el cast 'hashed' guarda el hash de la nueva contraseña; la anterior deja de servir.
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Cierra las sesiones abiertas de esa cuenta (por si alguien más la estaba usando).
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
                }

                event(new PasswordReset($user));
            }
        );

        if ($estado !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => self::MENSAJE_ENLACE_INVALIDO]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Tu contraseña fue actualizada. Ya puedes iniciar sesión con la nueva contraseña.');
    }
}
