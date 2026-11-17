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

    // El bloqueo por cuenta + IP (HU-05) se prueba en tests/Feature/Auth/LimiteIntentosTest.php.

    /** Capa extra: una IP no puede probar más de 20 correos distintos por minuto. */
    public function test_login_limita_intentos_por_ip(): void
    {
        User::factory()->create(['email' => 'ana@test.local', 'password' => 'Abcd1234']);

        for ($i = 0; $i < 20; $i++) {
            $this->from('/login')->post('/login', ['email' => "u{$i}@test.local", 'password' => 'Mala9999']);
        }

        $this->from('/login')
            ->post('/login', ['email' => 'ana@test.local', 'password' => 'Abcd1234'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => self::MENSAJE_LIMITE]);

        $this->assertGuest();
    }

    public function test_recuperar_limita_solicitudes_por_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/recuperar', ['email' => "u{$i}@test.local"]);
        }

        $this->from('/recuperar')
            ->post('/recuperar', ['email' => 'otro@test.local'])
            ->assertSessionHasErrors(['email' => self::MENSAJE_LIMITE]);
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
