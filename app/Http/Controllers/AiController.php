<?php

namespace App\Http\Controllers;

use App\Services\Ai\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiController extends Controller
{
    public function chat(Request $request, AiService $ai): JsonResponse
    {
        $datos = $request->validate([
            'mensaje' => 'required|string|max:1000',
            'historial' => 'sometimes|array|max:20',
            'historial.*.role' => 'required|in:user,assistant',
            'historial.*.content' => 'required|string|max:4000',
        ]);

        try {
            $respuesta = $ai->reply(
                $datos['mensaje'],
                $datos['historial'] ?? [],
                $request->user()
            );
        } catch (Throwable $e) {
            // El detalle técnico queda en storage/logs/laravel.log; al usuario solo un mensaje amable
            Log::error('AI: could not answer', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'El asistente no está disponible en este momento. Intenta de nuevo más tarde.',
            ], 503);
        }

        return response()->json(['respuesta' => $respuesta]);
    }
}