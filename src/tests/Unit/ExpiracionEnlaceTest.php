<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Pruebas unitarias UT-05 y UT-06 — expiración del token de recuperación (HU-03, CA-03.4).
 * Prueban directamente el password broker, sin pasar por HTTP.
 */
class ExpiracionEnlaceTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = User::factory()->create(['email' => 'ana@test.local']);
        $this->token = Password::broker()->createToken($this->usuario);
    }

    /** UT-05: el enlace dura 30 minutos y a los 29 sigue siendo válido */
    public function test_ut05_token_valido_antes_de_30_minutos(): void
    {
        $this->assertSame(30, (int) config('auth.passwords.users.expire'));

        $this->travel(29)->minutes();

        $this->assertTrue(Password::broker()->tokenExists($this->usuario, $this->token));
    }

    /** UT-06: a los 31 minutos el token expiró, y un token alterado nunca es válido */
    public function test_ut06_token_expirado_despues_de_30_minutos(): void
    {
        $this->assertFalse(Password::broker()->tokenExists($this->usuario, $this->token.'x'));

        $this->travel(31)->minutes();

        $this->assertFalse(Password::broker()->tokenExists($this->usuario, $this->token));
    }
}
