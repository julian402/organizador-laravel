<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * HU-04 — Cierre de sesión (casos CP-26 a CP-31).
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private User $ana;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ana = User::factory()->create([
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
        ]);
    }

    private function iniciarSesion(): void
    {
        $this->post('/login', ['email' => 'ana@test.local', 'password' => 'Abcd1234']);
        $this->assertAuthenticatedAs($this->ana);
    }

    /** CP-26 / CA-04.1: el usuario autenticado ve la opción de cerrar sesión */
    public function test_cp26_opcion_de_cerrar_sesion_visible(): void
    {
        $this->iniciarSesion();

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('Cerrar sesión');
    }

    /**
     * CP-27 / CA-04.2: la sesión se invalida en el servidor.
     * Se usan sesiones en BD (como en producción) para comprobar que el registro de la sesión se borra
     * y que la cookie anterior ya no da acceso.
     */
    public function test_cp27_cerrar_sesion_invalida_la_sesion_en_el_servidor(): void
    {
        config(['session.driver' => 'database']);

        $this->iniciarSesion();
        $idSesion = session()->getId();
        $this->assertDatabaseHas('sessions', ['id' => $idSesion, 'user_id' => $this->ana->id]);

        // El navegador envía la cookie de la sesión activa al cerrar sesión.
        $cookie = config('session.cookie');
        $this->withCookie($cookie, $idSesion)->post('/logout');

        $this->assertGuest();
        $this->assertDatabaseMissing('sessions', ['id' => $idSesion]);

        // Reutilizar la cookie anterior (p. ej. copiada por otra persona) no da acceso.
        $this->withCookie($cookie, $idSesion)->get('/dashboard')->assertRedirect('/login');
    }

    /** CP-28 / CA-04.3: redirige al inicio de sesión */
    public function test_cp28_redirige_al_login(): void
    {
        $this->iniciarSesion();

        $this->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Cerraste sesión correctamente.');
    }

    /** CP-29 / CA-04.4: después de cerrar sesión no se accede por URL directa */
    public function test_cp29_url_privada_despues_de_cerrar_sesion(): void
    {
        $this->iniciarSesion();
        $this->post('/logout');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    /** CP-30 / CA-04.4: las páginas privadas no se guardan en caché (botón Atrás) */
    public function test_cp30_paginas_privadas_no_se_guardan_en_cache(): void
    {
        $this->iniciarSesion();

        $respuesta = $this->get('/dashboard')->assertOk();

        $cacheControl = $respuesta->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
        $respuesta->assertHeader('Pragma', 'no-cache');
    }

    /** CP-31 / seguridad: no se puede cerrar sesión con GET (p. ej. un enlace o imagen externa) */
    public function test_cp31_cerrar_sesion_por_get_no_permitido(): void
    {
        $this->iniciarSesion();

        $this->get('/logout')->assertMethodNotAllowed();
        $this->assertAuthenticatedAs($this->ana);
    }

    /** Un visitante sin sesión que intenta cerrar sesión es enviado al login */
    public function test_visitante_no_puede_cerrar_sesion(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }
}
