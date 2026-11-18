<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\Concerns\ValidaContrasena;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Pruebas unitarias UT-03 y UT-04 — trait ValidaContrasena (HU-01 CA-01.4/01.5 y HU-03 CA-03.5).
 */
class ReglasContrasenaTest extends TestCase
{
    private function validar(string $clave, ?string $confirmacion = null): \Illuminate\Contracts\Validation\Validator
    {
        $reglas = new class
        {
            use ValidaContrasena {
                reglasContrasena as public;
                mensajesContrasena as public;
            }
        };

        return Validator::make(
            ['password' => $clave, 'password_confirmation' => $confirmacion ?? $clave],
            $reglas->reglasContrasena(),
            $reglas->mensajesContrasena(),
        );
    }

    /** UT-03: cada contraseña inválida se rechaza con el mensaje de la regla que incumple */
    public function test_ut03_rechaza_contrasenas_que_no_cumplen_las_reglas(): void
    {
        $casos = [
            'Abc1234' => 'La contraseña debe tener al menos 8 caracteres.',
            'abcd1234' => 'La contraseña debe tener al menos una letra mayúscula y una minúscula.',
            'ABCD1234' => 'La contraseña debe tener al menos una letra mayúscula y una minúscula.',
            'Abcdefgh' => 'La contraseña debe tener al menos un número.',
        ];

        foreach ($casos as $clave => $mensaje) {
            $validador = $this->validar($clave);
            $this->assertTrue($validador->fails(), "Debería rechazar: {$clave}");
            $this->assertContains($mensaje, $validador->errors()->get('password'), "Mensaje para {$clave}");
        }

        $this->assertSame(
            'La contraseña y su confirmación no coinciden.',
            $this->validar('Abcd1234', 'Abcd1235')->errors()->first('password'),
        );
    }

    /** UT-04: contraseñas que cumplen todas las reglas pasan (8 caracteres es el límite válido) */
    public function test_ut04_acepta_contrasenas_validas(): void
    {
        foreach (['Abcd1234', 'Nueva1234', 'aBCD1234', 'Clave-Segura-2026'] as $clave) {
            $this->assertTrue($this->validar($clave)->passes(), "Debería aceptar: {$clave}");
        }
    }
}
