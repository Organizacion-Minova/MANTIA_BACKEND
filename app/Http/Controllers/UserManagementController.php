<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auditoria;
use App\Support\ReglasUsuario;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Usuarios del panel de administración (Jefe de mina y Superadministrador).
 * Reglas de seguridad:
 *  - Nadie cambia su propio rol ni se desactiva a sí mismo.
 *  - Solo el Superadministrador puede dar o quitar el rol Superadministrador
 *    y tocar cuentas de otros Superadministradores.
 */
class UserManagementController extends Controller
{
    // Lista todos los usuarios con sus roles, para el panel de administración.
    public function index(Request $request)
    {
        $users = User::with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'last_name', 'email', 'status', 'is_active', 'document_type', 'document_number', 'email_verified_at', 'created_at'])
            ->map(function (User $u) use ($request) {
                $u->setAttribute('editable', $this->puedeGestionar($request->user(), $u));

                return $u;
            });

        return response()->json(['users' => $users]);
    }

    // Crea una cuenta desde el panel: queda activa y con el rol elegido (no necesita verificar correo).
    public function store(Request $request)
    {
        ReglasUsuario::normalizar($request);
        $tipo = is_string($request->input('document_type')) ? $request->input('document_type') : null;

        $datos = $request->validate([
            'name' => ReglasUsuario::nombre(),
            'last_name' => ReglasUsuario::nombre(),
            'email' => ReglasUsuario::correo(),
            'document_type' => ReglasUsuario::tipoDocumento(),
            'document_number' => ReglasUsuario::numeroDocumento($tipo),
            'password' => [...ReglasUsuario::contrasena(), 'confirmed'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('is_active', true)],
        ], ReglasUsuario::mensajes($tipo) + [
            'role.required' => 'Selecciona el rol del usuario.',
            'role.exists' => 'Ese rol no existe o está desactivado.',
        ]);

        if ($error = $this->validarRolAsignable($request->user(), $datos['role'])) {
            return $error;
        }

        // La creación queda en el historial con el registro de abajo (que incluye el rol)
        $user = Auditoria::sinRegistrar(fn () => DB::transaction(function () use ($datos) {
            $user = User::create([
                'name' => $datos['name'],
                'last_name' => $datos['last_name'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'document_type' => $datos['document_type'],
                'document_number' => $datos['document_number'],
                'status' => 'approved',
                'is_active' => true,
            ]);
            // Lo crea un administrador: el correo se da por verificado
            $user->forceFill(['email_verified_at' => now()])->save();
            $user->syncRoles([$datos['role']]);

            return $user;
        }));

        Auditoria::registrar(
            'user_created',
            'Usuarios',
            "Creó la cuenta de {$user->name} {$user->last_name} con el rol {$datos['role']}",
            null,
            ['email' => $user->email, 'role' => $datos['role']]
        );

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'user' => $user->load('roles:id,name'),
        ], 201);
    }

    // Activa o desactiva el acceso de un usuario (no borra la cuenta).
    public function toggleActive(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes desactivar tu propia cuenta.'], 422);
        }

        if (! $this->puedeGestionar($request->user(), $user)) {
            return response()->json(['message' => 'Solo un Superadministrador puede modificar esta cuenta.'], 403);
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
        if (! $user->is_active && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
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
            'password' => [...ReglasUsuario::contrasena('La nueva contraseña'), 'confirmed'],
        ], [
            'password.required' => 'Escribe la nueva contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user = User::findOrFail($id);

        if (! $this->puedeGestionar($request->user(), $user)) {
            return response()->json(['message' => 'Solo un Superadministrador puede modificar esta cuenta.'], 403);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Sus sesiones abiertas se cierran: debe entrar con la nueva contraseña
        if ($user->id !== $request->user()->id && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        Auditoria::registrar('password_reset', 'Usuarios', "Cambió la contraseña de {$user->name} {$user->last_name}");

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    // Cambia el rol asignado a un usuario.
    public function changeRole(Request $request, $id)
    {
        $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('is_active', true)],
        ], [
            'role.required' => 'Selecciona un rol.',
            'role.exists' => 'Ese rol no existe o está desactivado.',
        ]);

        $user = User::findOrFail($id);

        // Así el sistema nunca se queda sin administrador
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'No puedes cambiar tu propio rol.'], 422);
        }

        if (! $this->puedeGestionar($request->user(), $user)) {
            return response()->json(['message' => 'Solo un Superadministrador puede modificar esta cuenta.'], 403);
        }

        if ($error = $this->validarRolAsignable($request->user(), $request->role)) {
            return $error;
        }

        $antes = $user->roles->pluck('name')->implode(', ') ?: 'Sin rol';
        $user->syncRoles([$request->role]);

        Auditoria::registrar(
            'role_changed',
            'Usuarios',
            "Cambió el rol de {$user->name} {$user->last_name}: {$antes} → {$request->role}",
            ['role' => $antes],
            ['role' => $request->role]
        );

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'user' => $user->load('roles'),
        ]);
    }

    /** El Jefe de mina no puede tocar cuentas de Superadministradores. */
    private function puedeGestionar(User $yo, User $otro): bool
    {
        if (Roles::esSuperadmin($yo)) {
            return true;
        }

        return ! $otro->roles->contains('name', Roles::SUPERADMIN);
    }

    private function validarRolAsignable(User $yo, string $rol): ?JsonResponse
    {
        if ($rol === Roles::SUPERADMIN && ! Roles::esSuperadmin($yo)) {
            return response()->json(['message' => 'Solo un Superadministrador puede asignar ese rol.'], 403);
        }

        return null;
    }
}
