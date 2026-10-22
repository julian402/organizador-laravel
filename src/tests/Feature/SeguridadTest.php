<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Controles de seguridad transversales: límite de intentos y cabeceras HTTP.
 */
class SeguridadTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE_LIMITE = 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.';

    public function test_login_se_bloquea_tras_5_intentos_fallidos(): void
    {
        User::factory()->create(['email' => 'ana@test.local', 'password' => 'Abcd1234']);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', ['email' => 'ana@test.local', 'password' => 'Mala9999']);
        }

        // El 6.º intento se rechaza aunque la contraseña sea correcta.
        $this->from('/login')
            ->post('/login', ['email' => 'ana@test.local', 'password' => 'Abcd1234'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::MENSAJE_LIMITE]);

        $this->assertGuest();
    }

    public function test_limite_de_login_es_por_correo(): void
    {
        User::factory()->create(['email' => 'luis@test.local', 'password' => 'Abcd1234']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'ana@test.local', 'password' => 'Mala9999']);
        }

        // Otro correo desde la misma IP todavía puede entrar.
        $this->post('/login', ['email' => 'luis@test.local', 'password' => 'Abcd1234'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_registro_se_bloquea_tras_5_intentos(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/registro', ['name' => 'X', 'email' => "x{$i}@test.local"]);
        }

        $this->from('/registro')
            ->post('/registro', [
                'name' => 'Ana',
                'email' => 'ana@test.local',
                'password' => 'Abcd1234',
                'password_confirmation' => 'Abcd1234',
            ])
            ->assertRedirect('/registro')
            ->assertSessionHasErrors(['email' => self::MENSAJE_LIMITE]);

        $this->assertDatabaseMissing('users', ['email' => 'ana@test.local']);
    }

    public function test_respuestas_incluyen_cabeceras_de_seguridad(): void
    {
        config(['app.debug' => false]);

        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy')
            ->assertHeader('Content-Security-Policy')
            ->assertHeaderMissing('X-Powered-By');
    }
}
