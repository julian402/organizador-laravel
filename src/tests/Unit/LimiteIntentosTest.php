<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Pruebas unitarias UT-07 y UT-08 — LoginRequest::authenticate() (HU-05, CA-05.1 a CA-05.3).
 * Llaman al método directamente, sin rutas ni controlador.
 */
class LimiteIntentosTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE_BLOQUEO = 'Superaste el número de intentos permitidos';

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['email' => 'ana@test.local', 'password' => 'Abcd1234']);
    }

    /** Ejecuta authenticate() y devuelve el mensaje de error (null si autenticó). */
    private function autenticar(string $clave, string $ip = '10.0.0.1'): ?string
    {
        $request = LoginRequest::create('/login', 'POST', ['email' => 'ana@test.local', 'password' => $clave], [], [], ['REMOTE_ADDR' => $ip]);
        $request->setContainer($this->app)->setRedirector($this->app->make(Redirector::class));
        $request->validateResolved();

        try {
            $request->authenticate();
        } catch (ValidationException $e) {
            return $e->validator->errors()->first('email');
        }

        return null;
    }

    /** UT-07: los fallos 1 a 4 dan "credenciales no válidas"; el 5.º bloquea con el mensaje de 15 minutos */
    public function test_ut07_quinto_fallo_bloquea_la_cuenta(): void
    {
        for ($i = 1; $i <= 4; $i++) { // requisito CA-05.1: el 5.º fallo bloquea
            $this->assertSame(LoginRequest::MENSAJE_CREDENCIALES, $this->autenticar('Incorrecta1'), "Fallo {$i}");
        }

        $mensaje = $this->autenticar('Incorrecta1');

        $this->assertStringStartsWith(self::MENSAJE_BLOQUEO, $mensaje);
        $this->assertStringEndsWith('Inténtalo de nuevo en 15 minutos.', $mensaje);
    }

    /** UT-08: bloqueada, la contraseña correcta se rechaza; otra IP no está bloqueada; a los 15 min se desbloquea */
    public function test_ut08_bloqueo_rechaza_contrasena_correcta_y_expira(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->autenticar('Incorrecta1');
        }

        $this->assertStringStartsWith(self::MENSAJE_BLOQUEO, $this->autenticar('Abcd1234'));
        $this->assertNull($this->autenticar('Abcd1234', '10.0.0.2'), 'Otra IP no debe estar bloqueada');

        Auth::logout();
        $this->travel(14)->minutes();
        $this->assertStringEndsWith('en 1 minuto.', $this->autenticar('Abcd1234'));

        $this->travel(2)->minutes();
        $this->assertNull($this->autenticar('Abcd1234'));
    }
}
