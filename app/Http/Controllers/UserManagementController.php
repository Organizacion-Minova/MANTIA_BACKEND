<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    // Lista todos los usuarios con sus roles, para el panel de administración.
    public function index()
    {
        $users = User::with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'last_name', 'email', 'status', 'is_active', 'document_type', 'document_number', 'created_at']);

        return response()->json(['users' => $users]);
    }

    // Activa o desactiva el acceso de un usuario (no borra la cuenta).
    public function toggleActive(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes desactivar tu propia cuenta.'], 422);
        }

        $user->is_active = ! $user->is_active;
        // El login revisa "status": se mantiene igual que is_active
        if (! $user->is_active) {
            $user->status = 'inactive';
        } elseif ($user->status === 'inactive') {
            $user->status = 'approved';
        }
        $user->save();

        // Al desactivar, se cierran sus sesiones abiertas
        if (! $user->is_active) {
            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return response()->json([
            'message' => $user->is_active ? 'Usuario activado.' : 'Usuario desactivado.',
            'user' => $user,
        ]);
    }

    // Restablece la contraseña de un usuario (lo hace el administrador, no el propio usuario).
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    // Cambia el rol asignado a un usuario.
    public function changeRole(Request $request, $id)
    {
        $request->validate([
            'role' => ['required', 'string', \Illuminate\Validation\Rule::exists('roles', 'name')->where('is_active', true)],
        ], [
            'role.exists' => 'Ese rol no existe o está desactivado.',
        ]);

        $user = User::findOrFail($id);

        // Así el sistema nunca se queda sin Jefe de mina
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes cambiar tu propio rol.'], 422);
        }

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'user' => $user->load('roles'),
        ]);
    }
}