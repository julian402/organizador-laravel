<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * HU-01 — Registro de usuarios (casos CP-01 a CP-08 del plan TP-S01).
 */
class RegistroTest extends TestCase
{
    use RefreshDatabase;

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'name' => 'Ana Pérez',
            'email' => 'ana@test.local',
            'password' => 'Abcd1234',
            'password_confirmation' => 'Abcd1234',
        ], $cambios);
    }

    public function test_formulario_de_registro_se_muestra(): void
    {
        $this->get('/registro')
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    /** CP-01 / CA-01.6 */
    public function test_cp01_registro_con_datos_validos(): void
    {
        $this->post('/registro', $this->datosValidos())
            ->assertRedirect('/login')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', ['email' => 'ana@test.local']);
    }

    /** CP-02 / CA-01.2 */
    public function test_cp02_campos_vacios(): void
    {
        $this->post('/registro', [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'password_confirmation']);

        $this->assertDatabaseCount('users', 0);
    }

    /** CP-03 / CA-01.3 */
    public function test_cp03_correo_con_formato_invalido(): void
    {
        $this->post('/registro', $this->datosValidos(['email' => 'ana@correo']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    /** CP-04 / CA-01.3 */
    public function test_cp04_correo_duplicado_sin_distinguir_mayusculas(): void
    {
        User::factory()->create(['email' => 'ana@test.local']);

        $this->post('/registro', $this->datosValidos(['email' => 'ANA@test.local']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    /** CP-05 / CA-01.4 (límite inferior inválido) */
    public function test_cp05_contrasena_de_7_caracteres_rechazada(): void
    {
        $this->post('/registro', $this->datosValidos([
            'password' => 'Abc1234',
            'password_confirmation' => 'Abc1234',
        ]))->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    /** CP-06 / CA-01.4 (límite válido) */
    public function test_cp06_contrasena_de_8_caracteres_aceptada(): void
    {
        $this->post('/registro', $this->datosValidos())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('users', 1);
    }

    /** CA-01.4: requiere mayúscula, minúscula y número */
    public function test_contrasena_sin_mayuscula_o_sin_numero_rechazada(): void
    {
        foreach (['abcd1234', 'ABCD1234', 'Abcdefgh'] as $clave) {
            $this->post('/registro', $this->datosValidos([
                'password' => $clave,
                'password_confirmation' => $clave,
            ]))->assertSessionHasErrors('password');
        }

        $this->assertDatabaseCount('users', 0);
    }

    /** CP-07 / CA-01.5 */
    public function test_cp07_confirmacion_diferente(): void
    {
        $this->post('/registro', $this->datosValidos(['password_confirmation' => 'Abcd1235']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    /** CP-08 / CA-01.7 */
    public function test_cp08_contrasena_almacenada_con_hash(): void
    {
        $this->post('/registro', $this->datosValidos());

        $guardada = User::where('email', 'ana@test.local')->value('password');

        $this->assertNotSame('Abcd1234', $guardada);
        $this->assertTrue(Hash::check('Abcd1234', $guardada));
        $this->assertStringStartsWith('$2y$', $guardada);
    }
}
