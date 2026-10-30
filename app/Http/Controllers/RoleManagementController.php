<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    public function permissionsDisponibles()
{
    $modulos = [
        'location' => 'Ubicaciones',
        'location-category' => 'Categorías de ubicación',
        'machine' => 'Máquinas',
        'machine-category' => 'Categorías de máquina',
        'equipment' => 'Equipos',
        'tool' => 'Herramientas',
        'category-tools' => 'Categorías de herramientas',
        'gas-measurements' => 'Mediciones de gases',
        'daily-use' => 'Uso diario',
        'inspection' => 'Inspecciones',
    ];
    $acciones = ['view' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar'];

    $grupos = [];
    foreach ($modulos as $modulo => $etiquetaModulo) {
        $grupos[] = [
            'modulo' => $modulo,
            'etiqueta' => $etiquetaModulo,
            'permisos' => collect($acciones)->map(fn ($etiquetaAccion, $accion) => [
                'name' => "{$modulo}.{$accion}",
                'etiqueta' => $etiquetaAccion,
            ])->values(),
        ];
    }

    return response()->json(['grupos' => $grupos]);
}
    private const ROLES_PROTEGIDOS = ['Jefe de mina'];

    /** @return array<int,int> id del rol => cantidad de usuarios */
    private static function usuariosPorRol(): array
    {
        return \Illuminate\Support\Facades\DB::table('model_has_roles')
            ->where('model_type', \App\Models\User::class)
            ->selectRaw('role_id, count(*) as total')
            ->groupBy('role_id')
            ->pluck('total', 'role_id')
            ->all();
    }

 public function index()
{
    // Se cuenta directo en la tabla de asignaciones. Ni User::role() ni Role::users()
    // sirven aquí: fallan si un rol quedó guardado con un guard distinto de "web".
    $conteo = self::usuariosPorRol();

    $roles = Role::with('permissions:id,name')
        ->orderBy('name')
        ->get()
        ->map(function ($role) use ($conteo) {
            $role->users_count = (int) ($conteo[$role->id] ?? 0);

            return $role;
        });

    return response()->json(['roles' => $roles]);
}

// Lista de roles para el campo "Responsable" de uso diario y máquinas
public function responsables()
{
    $roles = Role::where('is_active', true)
        ->where('name', '!=', 'Visitante')
        ->orderBy('name')
        ->pluck('name');

    return response()->json(['roles' => $roles]);
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:50|unique:roles,name',
        'permissions' => 'array',
        'permissions.*' => 'string|exists:permissions,name',
    ]);

    $role = Role::create(['name' => $request->name, 'guard_name' => 'web', 'is_active' => true]);

    if ($request->filled('permissions')) {
        $role->syncPermissions($request->permissions);
    }

    return response()->json(['message' => 'Rol creado correctamente.', 'role' => $role->load('permissions:id,name')], 201);
}

public function update(Request $request, $id)
{
    $role = Role::findOrFail($id);

    $request->validate([
        'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role->id)],
        'permissions' => 'array',
        'permissions.*' => 'string|exists:permissions,name',
    ]);

    if (in_array($role->name, self::ROLES_PROTEGIDOS) && $request->name !== $role->name) {
        return response()->json(['message' => 'Este rol no se puede renombrar.'], 422);
    }

    $role->name = $request->name;
    $role->save();

    if ($request->has('permissions') && !in_array($role->name, self::ROLES_PROTEGIDOS)) {
        $role->syncPermissions($request->permissions);
    }

    return response()->json(['message' => 'Rol actualizado correctamente.', 'role' => $role->load('permissions:id,name')]);
}

    public function toggleActive($id)
    {
        $role = Role::findOrFail($id);

        if (in_array($role->name, self::ROLES_PROTEGIDOS)) {
            return response()->json(['message' => 'Este rol no se puede desactivar.'], 422);
        }

        $role->is_active = ! $role->is_active;
        $role->save();

        return response()->json([
            'message' => $role->is_active ? 'Rol activado.' : 'Rol desactivado.',
            'role' => $role,
        ]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->users_count = (int) (self::usuariosPorRol()[$role->id] ?? 0);

        if (in_array($role->name, self::ROLES_PROTEGIDOS)) {
            return response()->json(['message' => 'Este rol no se puede eliminar.'], 422);
        }

        if ($role->users_count > 0) {
            return response()->json([
                'message' => "No se puede eliminar: hay {$role->users_count} usuario(s) con este rol. Reasígnalos primero."
            ], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Rol eliminado correctamente.']);
    }
}