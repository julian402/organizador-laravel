<?php

namespace Tests\Unit;

use App\Http\Middleware\SinCache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Prueba unitaria UT-09 — middleware SinCache (HU-04, CA-04.4).
 */
class SinCacheTest extends TestCase
{
    /** UT-09: la respuesta sale con cabeceras que impiden guardarla en la caché del navegador */
    public function test_ut09_agrega_cabeceras_sin_cache(): void
    {
        $respuesta = (new SinCache)->handle(Request::create('/dashboard'), fn () => new Response('privado'));

        $cacheControl = $respuesta->headers->get('Cache-Control');
        foreach (['no-store', 'no-cache', 'must-revalidate', 'max-age=0', 'private'] as $directiva) {
            $this->assertStringContainsString($directiva, $cacheControl);
        }
        $this->assertSame('no-cache', $respuesta->headers->get('Pragma'));
        $this->assertSame('0', $respuesta->headers->get('Expires'));
        $this->assertSame('privado', $respuesta->getContent(), 'No debe alterar el contenido');
    }
}
