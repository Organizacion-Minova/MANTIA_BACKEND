<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AccountApprovedNotification;
use Illuminate\Http\Request;

class AdminApprovalController extends Controller
{
    public function pendingRequests(Request $request)
    {
        $users = User::where('status', 'pending')->get();
        return response()->json(['requests' => $users]);
    }

    // Aprobar usuario
    public function approve($id, Request $request)
    {
        $user = User::findOrFail($id);
        $user->status = 'approved';
        $user->save();

        // Enviar correo de aprobación
        $user->notify(new AccountApprovedNotification());

        return response()->json(['message' => 'Cuenta aprobada exitosamente']);
    }

    // Rechazar usuario
    public function reject($id, Request $request)
    {
        $user = User::findOrFail($id);
        $user->status = 'rejected';
        $user->save();

        return response()->json(['message' => 'Cuenta rechazada']);
    }
}
