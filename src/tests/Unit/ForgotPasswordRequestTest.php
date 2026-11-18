<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Prueba unitaria UT-10 — ForgotPasswordRequest (HU-03, CA-03.1).
 */
class ForgotPasswordRequestTest extends TestCase
{
    private function request(mixed $email): ForgotPasswordRequest
    {
        $request = ForgotPasswordRequest::create('/recuperar', 'POST', ['email' => $email]);
        $request->setContainer($this->app)->setRedirector($this->app->make(Redirector::class));

        return $request;
    }

    /** UT-10: normaliza el correo (minúsculas, sin espacios) y rechaza formatos inválidos */
    public function test_ut10_normaliza_y_valida_el_correo(): void
    {
        $valido = $this->request('  Ana@Test.LOCAL ');
        $valido->validateResolved();
        $this->assertSame('ana@test.local', $valido->input('email'));

        foreach (['', 'ana', 'ana@', 'ana @test.local', str_repeat('a', 250).'@test.local'] as $email) {
            try {
                $this->request($email)->validateResolved();
                $this->fail("Debería rechazar: '{$email}'");
            } catch (ValidationException $e) {
                $this->assertTrue($e->validator->errors()->has('email'));
            }
        }
    }
}
