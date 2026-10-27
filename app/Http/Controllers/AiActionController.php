<?php

namespace App\Http\Controllers;

use App\Models\AiPendingAction;
use App\Services\AdminActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Botones "Confirmar" y "Cancelar" de las acciones que propone MANTIA IA.
 * Las rutas están protegidas con role:Administrador (routes/api.php).
 */
class AiActionController extends Controller
{
    public function confirmar(Request $request, string $id, AdminActions $acciones): JsonResponse
    {
        return DB::transaction(function () use ($request, $id, $acciones) {
            // lockForUpdate: un doble clic no ejecuta la acción dos veces
            $accion = $this->pendiente($request, $id)->lockForUpdate()->first() ?? $this->noDisponible();

            // Si la validación falla (p. ej. el rol ya existe) Laravel responde 422 con el mensaje
            $mensaje = $acciones->ejecutar($accion->type, $accion->payload, $request->user());
            $accion->update(['executed_at' => now()]);

            Log::info('AI: admin action executed', [
                'admin' => $request->user()->email,
                'accion' => $accion->summary,
            ]);

            return response()->json(['message' => $mensaje]);
        });
    }

    public function cancelar(Request $request, string $id): JsonResponse
    {
        ($this->pendiente($request, $id)->first() ?? $this->noDisponible())->update(['cancelled_at' => now()]);

        return response()->json(['message' => 'Acción cancelada. No se hizo ningún cambio.']);
    }

    private function noDisponible(): never
    {
        abort(404, 'Esta acción ya no está disponible: venció, ya se hizo o se canceló.');
    }

    /** Solo acciones del mismo administrador, sin ejecutar, sin cancelar y que no hayan vencido. */
    private function pendiente(Request $request, string $id)
    {
        return AiPendingAction::whereKey($id)
            ->where('user_id', $request->user()->id)
            ->whereNull('executed_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now());
    }
}