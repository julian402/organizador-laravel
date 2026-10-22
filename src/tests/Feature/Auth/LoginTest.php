<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-02 — Inicio de sesión (casos CP-09 a CP-14 del plan TP-S01).
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE_GENERICO = 'Las credenciales ingresadas no son válidas.';

    protected function setUp(): void
    {
        parent::setUp();

        // Precondición: usuario ana@test.local / Abcd1234
        User::factory()->create([
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
        ]);
    }

    public function test_formulario_de_login_se_muestra(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    /** CP-09 / CA-02.2 y CA-02.5 */
    public function test_cp09_credenciales_correctas(): void
    {
        $this->post('/login', ['email' => 'ana@test.local', 'password' => 'Abcd1234'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    /** CP-10 / CA-02.3 */
    public function test_cp10_contrasena_incorrecta(): void
    {
        $this->post('/login', ['email' => 'ana@test.local', 'password' => 'Abcd1235'])
            ->assertSessionHasErrors(['email' => self::MENSAJE_GENERICO]);

        $this->assertGuest();
    }

    /** CP-11 / CA-02.3: mismo mensaje que CP-10, no revela si la cuenta existe */
    public function test_cp11_correo_no_registrado(): void
    {
        $this->post('/login', ['email' => 'nadie@test.local', 'password' => 'Abcd1234'])
            ->assertSessionHasErrors(['email' => self::MENSAJE_GENERICO]);

        $this->assertGuest();
    }

    /** CP-12 / CA-02.4 */
    public function test_cp12_campos_vacios(): void
    {
        $this->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    /** CP-13 / CA-02.6 */
    public function test_cp13_url_privada_sin_sesion(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    /** CP-14 / CA-02.5 */
    public function test_cp14_navegar_al_area_privada_con_sesion(): void
    {
        $this->post('/login', ['email' => 'ana@test.local', 'password' => 'Abcd1234']);

        $this->get('/dashboard')->assertOk()->assertSee('Hola,');
    }

    /** El correo se acepta sin importar mayúsculas */
    public function test_login_con_correo_en_mayusculas(): void
    {
        $this->post('/login', ['email' => 'ANA@test.local', 'password' => 'Abcd1234'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }
}
