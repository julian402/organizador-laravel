<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * HU-01 / CA-01.3: el correo debe ser único al registrar.
 */
class UniqueEmailTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE_DUPLICADO = 'Ya existe una cuenta registrada con este correo electrónico.';

    /**
     * Valida los datos con RegisterRequest y devuelve los errores (vacío si pasa).
     */
    private function validar(array $datos): MessageBag
    {
        $request = RegisterRequest::create('/register', 'POST', $datos);
        $request->setContainer($this->app)
            ->setRedirector($this->app->make(Redirector::class));

        try {
            // Ejecuta prepareForValidation() (minúsculas + trim) y luego las reglas.
            $request->validateResolved();
        } catch (ValidationException $e) {
            return $e->validator->errors();
        }

        return new MessageBag();
    }

    /**
     * Datos válidos de registro; $cambios sobrescribe solo lo necesario.
     */
    private function datos(array $cambios = []): array
    {
        return array_merge([
            'name' => 'Ana Pérez',
            'email' => 'ana@test.local',
            'password' => 'Secreta123',
            'password_confirmation' => 'Secreta123',
        ], $cambios);
    }

    public function test_acepta_un_email_que_no_existe(): void
    {
        $errores = $this->validar($this->datos());

        $this->assertFalse($errores->has('email'));
    }

    public function test_rechaza_un_email_que_ya_esta_registrado(): void
    {
        User::factory()->create(['email' => 'ana@test.local']);

        $errores = $this->validar($this->datos());

        $this->assertTrue($errores->has('email'));
        $this->assertSame(self::MENSAJE_DUPLICADO, $errores->first('email'));
    }

    // public function test_rechaza_el_mismo_email_escrito_en_mayusculas(): void
    // {
    //     User::factory()->create(['email' => 'ana@test.local']);

    //     $errores = $this->validar($this->datos(['email' => 'ANA@Test.Local']));

    //     $this->assertSame(self::MENSAJE_DUPLICADO, $errores->first('email'));
    // }

    // public function test_rechaza_el_mismo_email_con_espacios_alrededor(): void
    // {
    //     User::factory()->create(['email' => 'ana@test.local']);

    //     $errores = $this->validar($this->datos(['email' => '  ana@test.local  ']));

    //     $this->assertSame(self::MENSAJE_DUPLICADO, $errores->first('email'));
    // }

    // public function test_acepta_un_email_distinto_aunque_existan_otros_usuarios(): void
    // {
    //     User::factory()->create(['email' => 'ana@test.local']);

    //     $errores = $this->validar($this->datos(['email' => 'luis@test.local']));

    //     $this->assertFalse($errores->has('email'));
    // }
}
