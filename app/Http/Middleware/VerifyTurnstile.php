<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Captcha de Cloudflare Turnstile.
 * El frontend manda el resultado del captcha en el campo "captcha_token";
 * aquí se le pregunta a Cloudflare si es válido. Sin captcha válido, la petición no pasa.
 */
class VerifyTurnstile
{
    private const URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function handle(Request $request, Closure $next): Response
    {
        $secreto = config('services.turnstile.secret');

        if (! $secreto) {
            // Sin clave configurada solo se deja pasar en desarrollo; en producción se bloquea.
            if (app()->environment('local', 'testing')) {
                return $next($request);
            }

            return response()->json(['message' => 'El captcha no está configurado en el servidor.'], 500);
        }

        $token = $request->input('captcha_token');

        if (! is_string($token) || $token === '') {
            return response()->json(['message' => 'Completa la verificación de seguridad (captcha).'], 422);
        }

        try {
            $respuesta = Http::asForm()->timeout(5)->post(self::URL, [
                'secret' => $secreto,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo verificar el captcha. Intenta de nuevo.'], 503);
        }

        if (! $respuesta->json('success')) {
            return response()->json(['message' => 'No se pudo verificar el captcha. Inténtalo de nuevo.'], 422);
        }

        return $next($request);
    }
}