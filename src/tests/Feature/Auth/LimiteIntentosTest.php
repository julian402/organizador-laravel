<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-05 — Límite de intentos de autenticación fallidos (casos CP-32 a CP-39).
 */
class LimiteIntentosTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE_BLOQUEO_15 = 'Superaste el número de intentos permitidos. Por seguridad, el inicio de sesión '
        .'quedó bloqueado temporalmente. Inténtalo de nuevo en 15 minutos.';

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['email' => 'ana@test.local', 'password' => 'Abcd1234']);
        User::factory()->create(['email' => 'luis@test.local', 'password' => 'Abcd1234']);
    }

    private function desdeIp(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    private function intentar(string $email, string $clave)
    {
        return $this->from('/login')->post('/login', ['email' => $email, 'password' => $clave]);
    }

    private function fallar(int $veces, string $email = 'ana@test.local'): void
    {
        for ($i = 0; $i < $veces; $i++) {
            $this->intentar($email, 'Mala9999');
        }
    }

    private function cerrarSesion(): void
    {
        $this->post('/logout');
        $this->assertGuest();
    }

    /** CP-32 / CA-05.1 (límite válido): con 4 fallos todavía se puede entrar */
    public function test_cp32_cuatro_fallos_aun_permiten_entrar(): void
    {
        $this->fallar(4);

        $this->intentar('ana@test.local', 'Abcd1234')->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** CP-33 / CA-05.1 y CA-05.2: el 5.º fallo bloquea; el 6.º intento se rechaza aunque la clave sea correcta */
    public function test_cp33_quinto_fallo_bloquea_la_cuenta_en_esa_ip(): void
    {
        $this->fallar(4);

        $this->intentar('ana@test.local', 'Mala9999')
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::MENSAJE_BLOQUEO_15]);

        $this->intentar('ana@test.local', 'Abcd1234')
            ->assertSessionHasErrors(['email' => self::MENSAJE_BLOQUEO_15]);

        $this->assertGuest();
    }

    /** CP-34 / CA-05.3: el mensaje es comprensible y no revela si la cuenta existe */
    public function test_cp34_mensaje_igual_para_cuenta_inexistente(): void
    {
        // Los 4 primeros fallos muestran el mensaje genérico de HU-02.
        $this->intentar('ana@test.local', 'Mala9999')
            ->assertSessionHasErrors(['email' => LoginRequest::MENSAJE_CREDENCIALES]);

        $this->fallar(4);
        $this->fallar(5, 'nadie@test.local');

        $this->intentar('ana@test.local', 'Abcd1234')
            ->assertSessionHasErrors(['email' => self::MENSAJE_BLOQUEO_15]);
        $this->intentar('nadie@test.local', 'Abcd1234')
            ->assertSessionHasErrors(['email' => self::MENSAJE_BLOQUEO_15]);
    }

    /** CP-35 / CA-05.2: a los 14 minutos sigue bloqueado */
    public function test_cp35_sigue_bloqueado_a_los_14_minutos(): void
    {
        $this->fallar(5);

        $this->travel(14)->minutes();

        $this->intentar('ana@test.local', 'Abcd1234')
            ->assertSessionHasErrors(['email' => str_replace('15 minutos', '1 minuto', self::MENSAJE_BLOQUEO_15)]);
        $this->assertGuest();
    }

    /** CP-36 / CA-05.4: pasados los 15 minutos se puede volver a intentar */
    public function test_cp36_despues_del_bloqueo_se_puede_entrar(): void
    {
        $this->fallar(5);

        $this->travel(15 * 60 + 1)->seconds();

        $this->intentar('ana@test.local', 'Abcd1234')->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** CA-05.4: tras el bloqueo el contador empieza de cero (no se bloquea al primer fallo) */
    public function test_despues_del_bloqueo_el_contador_empieza_de_cero(): void
    {
        $this->fallar(5);
        $this->travel(16)->minutes();

        $this->intentar('ana@test.local', 'Mala9999')
            ->assertSessionHasErrors(['email' => LoginRequest::MENSAJE_CREDENCIALES]);
    }

    /** CP-37 / CA-05.5: un inicio de sesión exitoso reinicia el contador */
    public function test_cp37_login_exitoso_reinicia_el_contador(): void
    {
        $this->fallar(4);
        $this->intentar('ana@test.local', 'Abcd1234')->assertRedirect('/dashboard');
        $this->cerrarSesion();

        // Si el contador no se reiniciara, el siguiente fallo (5.º) bloquearía.
        $this->fallar(4);
        $this->intentar('ana@test.local', 'Abcd1234')->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    /** CP-38 / CA-05.1: el límite es por combinación de cuenta e IP */
    public function test_cp38_el_bloqueo_es_por_cuenta_e_ip(): void
    {
        $this->desdeIp('10.0.0.1');
        $this->fallar(5);

        // Otra cuenta desde la misma IP no está bloqueada.
        $this->intentar('luis@test.local', 'Abcd1234')->assertRedirect('/dashboard');
        $this->cerrarSesion();

        // La misma cuenta desde otra IP no está bloqueada.
        $this->desdeIp('10.0.0.2');
        $this->intentar('ana@test.local', 'Abcd1234')->assertRedirect('/dashboard');
        $this->cerrarSesion();

        // La combinación original sigue bloqueada.
        $this->desdeIp('10.0.0.1');
        $this->intentar('ana@test.local', 'Abcd1234')
            ->assertSessionHasErrors(['email' => self::MENSAJE_BLOQUEO_15]);
    }

    /** CP-39 / CA-05.1: los fallos separados por más de 15 minutos no se acumulan */
    public function test_cp39_ventana_de_15_minutos(): void
    {
        $this->fallar(3);
        $this->travel(16)->minutes();
        $this->fallar(3);

        $this->intentar('ana@test.local', 'Abcd1234')->assertRedirect('/dashboard');
    }

    /** CA-05.1: el correo en mayúsculas cuenta para la misma cuenta */
    public function test_mayusculas_no_evitan_el_limite(): void
    {
        $this->fallar(3);
        $this->fallar(2, 'ANA@TEST.LOCAL');

        $this->intentar('ana@test.local', 'Abcd1234')
            ->assertSessionHasErrors(['email' => self::MENSAJE_BLOQUEO_15]);
    }

    /** Los envíos con campos vacíos no cuentan como intentos fallidos */
    public function test_campos_vacios_no_cuentan_como_fallo(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->from('/login')->post('/login', ['email' => 'ana@test.local', 'password' => '']);
        }

        $this->intentar('ana@test.local', 'Abcd1234')->assertRedirect('/dashboard');
    }
}
