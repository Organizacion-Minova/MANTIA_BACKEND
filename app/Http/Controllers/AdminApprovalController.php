<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AccountApprovedNotification;
use App\Support\Roles;
use Illuminate\Http\Request;

/**
 * Solicitudes de cuenta pendientes. Toda cuenta nueva entra con el rol Visitante;
 * el administrador cambia el rol después desde la pestaña Usuarios.
 */
class AdminApprovalController extends Controller
{
    public function pendingRequests(Request $request)
    {
        $users = User::with('roles:id,name')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'last_name', 'email', 'document_type', 'document_number', 'status', 'email_verified_at', 'verification_expires_at', 'created_at'])
            ->map(function (User $u) {
                // El rol con el que entra a la página (siempre Visitante al registrarse)
                $u->setAttribute('rol', $u->roles->first()?->name ?? Roles::VISITANTE);
                $u->setAttribute('correo_verificado', (bool) $u->email_verified_at);

                return $u;
            });

        return response()->json(['requests' => $users]);
    }

    public function approve($id, Request $request)
    {
        $user = User::where('status', 'pending')->findOrFail($id);
        $user->status = 'approved';
        $user->save();

        // El rol nunca viene de lo que pidió la persona: queda como Visitante.
        if ($user->roles()->count() === 0) {
            $user->assignRole(Roles::VISITANTE);
        }

        try {
            $user->notify(new AccountApprovedNotification());
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['message' => 'Cuenta aprobada exitosamente']);
    }

    public function reject($id, Request $request)
    {
        $user = User::where('status', 'pending')->findOrFail($id);
        $user->status = 'rejected';
        $user->save();

        return response()->json(['message' => 'Cuenta rechazada']);
    }
}
