<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Models\User;
use App\Notifications\RestablecerContrasena;
use App\Rules\DistintaDeLaActual;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * HU-03 — Recuperación de contraseña (casos CP-15 a CP-25 y CP-40 a CP-42).
 */
class RecuperarContrasenaTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();

        // No se envían correos reales: se capturan las notificaciones.
        Notification::fake();

        // Precondición: usuario ana@test.local / Abcd1234
        $this->ana = User::factory()->create([
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
        ]);
    }

    /** Solicita el enlace para ana@test.local y devuelve el token que llegó en el correo. */
    private function solicitarToken(): string
    {
        $this->post('/recuperar', ['email' => 'ana@test.local']);

        $token = null;
        Notification::assertSentTo($this->ana, RestablecerContrasena::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        return $token;
    }

    private function restablecer(string $token, array $cambios = [])
    {
        return $this->from('/restablecer/'.$token)->post('/restablecer', array_merge([
            'token' => $token,
            'email' => 'ana@test.local',
            'password' => 'Nueva1234',
            'password_confirmation' => 'Nueva1234',
        ], $cambios));
    }

    private function contrasenaActualEs(string $clave): bool
    {
        return Hash::check($clave, $this->ana->fresh()->password);
    }

    /** CP-15 / CA-03.1 */
    public function test_cp15_formulario_de_recuperacion_se_muestra(): void
    {
        $this->get('/login')->assertSee(route('password.request'));

        $this->get('/recuperar')
            ->assertOk()
            ->assertSee('name="email"', false);
    }

    /** CP-16 / CA-03.2 y CA-03.3: correo registrado */
    public function test_cp16_correo_registrado_recibe_enlace_y_mensaje_generico(): void
    {
        $this->from('/recuperar')
            ->post('/recuperar', ['email' => 'ANA@test.local'])
            ->assertRedirect('/recuperar')
            ->assertSessionHas('status', ForgotPasswordController::MENSAJE_GENERICO);

        Notification::assertSentTo($this->ana, RestablecerContrasena::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'ana@test.local']);
    }

    /** CP-17 / CA-03.2: correo no registrado, misma respuesta y no se envía nada */
    public function test_cp17_correo_no_registrado_recibe_el_mismo_mensaje(): void
    {
        $this->from('/recuperar')
            ->post('/recuperar', ['email' => 'nadie@test.local'])
            ->assertRedirect('/recuperar')
            ->assertSessionHas('status', ForgotPasswordController::MENSAJE_GENERICO)
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    /** CA-03.1: el correo es obligatorio y con formato válido */
    public function test_correo_vacio_o_invalido_se_rechaza(): void
    {
        $this->post('/recuperar', ['email' => ''])->assertSessionHasErrors('email');
        $this->post('/recuperar', ['email' => 'ana@correo'])->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    /** CP-18 / CA-03.3: token aleatorio, guardado solo como hash y enlace con la URL de la app */
    public function test_cp18_token_seguro_y_enlace_correcto(): void
    {
        $token = $this->solicitarToken();

        $this->assertSame(64, strlen($token));

        $guardado = DB::table('password_reset_tokens')->where('email', 'ana@test.local')->value('token');
        $this->assertNotSame($token, $guardado);
        $this->assertTrue(Hash::check($token, $guardado));

        $enlace = (new RestablecerContrasena($token))->url($this->ana);
        $this->assertStringStartsWith(rtrim(config('app.url'), '/').'/restablecer/'.$token, $enlace);
    }

    /** CA-03.3: un Host falso en la petición no cambia el dominio del enlace enviado por correo */
    public function test_enlace_no_usa_el_host_de_la_peticion(): void
    {
        $this->post('http://sitio-malicioso.test/recuperar', ['email' => 'ana@test.local']);

        Notification::assertSentTo($this->ana, RestablecerContrasena::class, function ($n) {
            $enlace = $n->toMail($this->ana)->actionUrl;

            return str_starts_with($enlace, rtrim(config('app.url'), '/'))
                && ! str_contains($enlace, 'sitio-malicioso');
        });
    }

    /** CP-19 / CA-03.3: el enlace válido muestra el formulario y permite cambiar la contraseña */
    public function test_cp19_restablecer_con_enlace_valido(): void
    {
        $token = $this->solicitarToken();

        $this->get('/restablecer/'.$token.'?email=ana@test.local')
            ->assertOk()
            ->assertSee('name="password"', false);

        $this->restablecer($token)
            ->assertRedirect('/login')
            ->assertSessionHas('status');

        $this->assertTrue($this->contrasenaActualEs('Nueva1234'));
    }

    /** CP-20 / CA-03.4 (límite válido): a los 29 minutos el enlace todavía sirve */
    public function test_cp20_enlace_valido_a_los_29_minutos(): void
    {
        $token = $this->solicitarToken();

        $this->travel(29)->minutes();

        $this->restablecer($token)->assertRedirect('/login');
        $this->assertTrue($this->contrasenaActualEs('Nueva1234'));
    }

    /** CP-21 / CA-03.4: a los 31 minutos el enlace expiró */
    public function test_cp21_enlace_expirado_a_los_31_minutos(): void
    {
        $token = $this->solicitarToken();

        $this->travel(31)->minutes();

        $this->get('/restablecer/'.$token.'?email=ana@test.local')
            ->assertSee(ResetPasswordController::MENSAJE_ENLACE_INVALIDO);

        $this->restablecer($token)
            ->assertSessionHasErrors(['email' => ResetPasswordController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertTrue($this->contrasenaActualEs('Abcd1234'));
    }

    /** CP-22 / CA-03.5: la nueva contraseña cumple las reglas de HU-01 */
    public function test_cp22_nueva_contrasena_debe_cumplir_reglas_de_hu01(): void
    {
        $token = $this->solicitarToken();

        foreach (['Abc1234', 'abcd1234', 'ABCD1234', 'Abcdefgh'] as $clave) {
            $this->restablecer($token, ['password' => $clave, 'password_confirmation' => $clave])
                ->assertSessionHasErrors('password');
        }

        $this->restablecer($token, ['password_confirmation' => 'Nueva1235'])
            ->assertSessionHasErrors('password');

        $this->assertTrue($this->contrasenaActualEs('Abcd1234'));

        // Los intentos inválidos no consumen el token: con una contraseña válida sí funciona.
        $this->restablecer($token)->assertRedirect('/login');
    }

    /** CP-23 / CA-03.6: el token es de un solo uso */
    public function test_cp23_enlace_ya_usado_se_rechaza(): void
    {
        $token = $this->solicitarToken();

        $this->restablecer($token)->assertRedirect('/login');
        $this->assertDatabaseCount('password_reset_tokens', 0);

        $this->restablecer($token, ['password' => 'Otra12345', 'password_confirmation' => 'Otra12345'])
            ->assertSessionHasErrors(['email' => ResetPasswordController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertTrue($this->contrasenaActualEs('Nueva1234'));
    }

    /** CP-24 / CA-03.7: la contraseña anterior deja de servir y la nueva sí */
    public function test_cp24_contrasena_anterior_ya_no_permite_entrar(): void
    {
        $token = $this->solicitarToken();
        $this->restablecer($token);

        $this->from('/login')->post('/login', ['email' => 'ana@test.local', 'password' => 'Abcd1234'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'ana@test.local', 'password' => 'Nueva1234'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->ana);
    }

    /** CP-25 / seguridad: un token inventado o de otra cuenta no sirve */
    public function test_cp25_token_manipulado_se_rechaza(): void
    {
        $this->solicitarToken();

        $this->restablecer(str_repeat('a', 64))
            ->assertSessionHasErrors(['email' => ResetPasswordController::MENSAJE_ENLACE_INVALIDO]);

        $this->assertTrue($this->contrasenaActualEs('Abcd1234'));
    }

    /** Seguridad: pedir un enlace nuevo invalida el anterior */
    public function test_nuevo_enlace_invalida_el_anterior(): void
    {
        $primero = $this->solicitarToken();

        $this->travel(2)->minutes(); // Laravel exige 60 s entre solicitudes de la misma cuenta.
        Notification::fake();
        $segundo = $this->solicitarToken();

        $this->assertNotSame($primero, $segundo);
        $this->restablecer($primero)->assertSessionHasErrors('email');
        $this->restablecer($segundo)->assertRedirect('/login');
    }

    /** Seguridad: al cambiar la contraseña se renueva el remember_token (invalida "recordarme") */
    public function test_restablecer_renueva_remember_token(): void
    {
        $this->ana->forceFill(['remember_token' => 'token-viejo'])->save();

        $this->restablecer($this->solicitarToken());

        $this->assertNotSame('token-viejo', $this->ana->fresh()->remember_token);
    }

    /** Un usuario con sesión iniciada no usa el flujo de recuperación */
    public function test_usuario_autenticado_no_accede_a_recuperar(): void
    {
        $this->actingAs($this->ana)->get('/recuperar')->assertRedirect('/dashboard');
    }

    /** CP-40 / CA-03.8: la nueva contraseña no puede ser igual a la actual */
    public function test_cp40_nueva_contrasena_igual_a_la_actual_se_rechaza(): void
    {
        $token = $this->solicitarToken();

        $this->restablecer($token, ['password' => 'Abcd1234', 'password_confirmation' => 'Abcd1234'])
            ->assertRedirect('/restablecer/'.$token)
            ->assertSessionHasErrors(['password' => DistintaDeLaActual::MENSAJE])
            ->assertSessionHasInput('email', 'ana@test.local');

        // No se cambió nada y el enlace NO se consumió: se puede corregir y reintentar.
        $this->assertTrue($this->contrasenaActualEs('Abcd1234'));
        $this->assertDatabaseCount('password_reset_tokens', 1);

        $this->restablecer($token)->assertRedirect('/login');
        $this->assertTrue($this->contrasenaActualEs('Nueva1234'));
    }

    /** CP-41 / CA-03.8 (límite): una contraseña que solo cambia mayúsculas/minúsculas sí es distinta */
    public function test_cp41_contrasena_que_solo_cambia_mayusculas_se_acepta(): void
    {
        $token = $this->solicitarToken();

        $this->restablecer($token, ['password' => 'aBCD1234', 'password_confirmation' => 'aBCD1234'])
            ->assertRedirect('/login')
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->contrasenaActualEs('aBCD1234'));
        $this->assertFalse($this->contrasenaActualEs('Abcd1234'));
    }

    /** CP-42 / seguridad: sin un enlace válido no se revela si una contraseña es la actual */
    public function test_cp42_sin_enlace_valido_no_se_revela_la_contrasena_actual(): void
    {
        $this->solicitarToken();

        $this->restablecer(str_repeat('b', 64), ['password' => 'Abcd1234', 'password_confirmation' => 'Abcd1234'])
            ->assertSessionHasErrors(['email' => ResetPasswordController::MENSAJE_ENLACE_INVALIDO])
            ->assertSessionDoesntHaveErrors('password');

        $this->assertTrue($this->contrasenaActualEs('Abcd1234'));
    }
}
