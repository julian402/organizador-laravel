<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HU-04 (CA-04.4): las páginas privadas no se guardan en la caché del navegador,
 * así que tras cerrar sesión el botón "Atrás" no puede mostrarlas sin volver a pedirlas al servidor
 * (y el servidor, sin sesión, redirige al login).
 */
class SinCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
