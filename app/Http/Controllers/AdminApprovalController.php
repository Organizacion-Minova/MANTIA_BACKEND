<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AccountApprovedNotification;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

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

    // El rol nunca viene de lo que pidió la persona: si no tiene rol, queda como Visitante.
    // El Jefe de mina cambia el rol después desde el panel.
    if ($user->roles()->count() === 0) {
        $user->assignRole('Visitante');
    }

        try {
        $user->notify(new AccountApprovedNotification());
    } catch (\Throwable $e) {
        report($e);
    }

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
