<?php

namespace App\Http\Middleware;

use App\Services\CaptchaLocal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captcha LOCAL (no necesita internet). Ver App\Services\CaptchaLocal.
 * El frontend manda "captcha_token" con el formato  id:respuesta.
 * Se apaga con CAPTCHA_ENABLED=false (las pruebas automatizadas lo apagan).
 */
class VerifyCaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.captcha.enabled', true)) {
            return $next($request);
        }

        $token = $request->input('captcha_token');

        if (! is_string($token) || ! str_contains($token, ':')) {
            return response()->json([
                'message' => 'Escribe los caracteres de la imagen de seguridad.',
                'codigo' => 'captcha_requerido',
            ], 422);
        }

        [$id, $respuesta] = explode(':', $token, 2);

        if (! CaptchaLocal::verificar($id, $respuesta)) {
            return response()->json([
                'message' => 'Los caracteres de la imagen no coinciden o la imagen venció. Intenta con la nueva imagen.',
                'codigo' => 'captcha_invalido',
            ], 422);
        }

        return $next($request);
    }
}
