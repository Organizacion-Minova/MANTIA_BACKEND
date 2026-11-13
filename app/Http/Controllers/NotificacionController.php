<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->notifications()
            ->latest()
            ->limit(50)
            ->get(['id', 'data', 'read_at', 'created_at']);
    }

    /**
     * Historial para la pantalla Alertas: más reciente primero, hasta 1000.
     * La campana sigue usando index() (solo las últimas 50).
     */
    public function historial(Request $request)
    {
        return $request->user()
            ->notifications()
            ->latest()
            ->limit(1000)
            ->get(['id', 'data', 'read_at', 'created_at']);
    }

    public function leer(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    public function leerTodas(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Todas marcadas como leídas']);
    }
}