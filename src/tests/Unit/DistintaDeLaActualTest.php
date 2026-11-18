<?php

namespace Tests\Unit;

use App\Models\User;
use App\Rules\DistintaDeLaActual;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Pruebas unitarias UT-01 y UT-02 — regla DistintaDeLaActual (HU-03, CA-03.8).
 * No usan la base de datos: el usuario solo existe en memoria con su hash.
 */
class DistintaDeLaActualTest extends TestCase
{
    private function validar(string $clave): \Illuminate\Contracts\Validation\Validator
    {
        $usuario = new User(['password' => 'Abcd1234']); // el cast 'hashed' guarda el hash

        return Validator::make(['password' => $clave], ['password' => [new DistintaDeLaActual($usuario)]]);
    }

    /** UT-01: la misma contraseña actual se rechaza con el mensaje esperado */
    public function test_ut01_rechaza_la_contrasena_actual(): void
    {
        $validador = $this->validar('Abcd1234');

        $this->assertTrue($validador->fails());
        $this->assertSame(DistintaDeLaActual::MENSAJE, $validador->errors()->first('password'));
    }

    /** UT-02: contraseñas distintas (incluida una que solo cambia mayúsculas) se aceptan */
    public function test_ut02_acepta_contrasenas_distintas(): void
    {
        foreach (['Nueva1234', 'aBCD1234', 'Abcd12345', ' Abcd1234'] as $clave) {
            $this->assertTrue($this->validar($clave)->passes(), "Debería aceptar: '{$clave}'");
        }
    }
}
