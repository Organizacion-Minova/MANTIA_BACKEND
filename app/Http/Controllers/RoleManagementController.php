<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Services\Auditoria;
use App\Support\Roles;

class RoleManagementController extends Controller
{
    public function permissionsDisponibles()
    {
        $modulos = [
            'location' => ['Ubicaciones', 'Pantalla de ubicaciones y su uso en máquinas, equipos y herramientas.'],
            'location-category' => ['Categorías de ubicación', 'Tipos de ubicación (frentes, bodegas, talleres...).'],
            'machine' => ['Máquinas', 'Hojas de vida de máquinas, ficha técnica, exportes y consumibles asignados.'],
            'machine-category' => ['Categorías de máquina', 'Tipos de máquina.'],
            'equipment' => ['Equipos', 'Hojas de vida de equipos consumibles y no consumibles, ficha técnica y exportes.'],
            'tool' => ['Herramientas', 'Inventario de herramientas consumibles y no consumibles y su stock.'],
            'category-tools' => ['Categorías de herramientas', 'Tipos de herramientas.'],
            'gas-measurements' => ['Mediciones de gases', 'Formato de medición de gases, comparación y exportes.'],
            'daily-use' => ['Uso diario', 'Formato de uso diario de máquinas y sus exportes.'],
            'inspection' => ['Inspecciones', 'Formatos de inspección y preoperacionales de máquinas y equipos: consultar, llenar registros y diseñar o editar formatos.'],
        ];
        $acciones = ['view' => 'Ver', 'create' => 'Crear', 'update' => 'Editar', 'delete' => 'Eliminar'];
        $descripciones = [
            'view' => 'Permite consultar y ver %s.',
            'create' => 'Permite crear %s.',
            'update' => 'Permite editar %s.',
            'delete' => 'Permite eliminar %s.',
        ];

        $grupos = [];
        foreach ($modulos as $modulo => [$etiquetaModulo, $descripcion]) {
            $accionesModulo = $acciones;
            if ($modulo === 'inspection') {
                unset($accionesModulo['delete']);
            }

            $grupos[] = [
                'modulo' => $modulo,
                'etiqueta' => $etiquetaModulo,
                'descripcion' => $descripcion,
                'permisos' => collect($accionesModulo)->map(fn ($etiquetaAccion, $accion) => [
                    'name' => "{$modulo}.{$accion}",
                    'etiqueta' => $etiquetaAccion,
                    'descripcion' => sprintf($descripciones[$accion], mb_strtolower($etiquetaModulo)),
                ])->values(),
            ];
        }

        return response()->json(['grupos' => $grupos]);
    }
    // No se renombran, desactivan ni eliminan (Visitante es el rol con el que entra toda cuenta nueva)
    private const ROLES_PROTEGIDOS = Roles::PROTEGIDOS;

    // Sus permisos no se editan: siempre tienen acceso a todo
    private const ROLES_TODO_ACCESO = [Roles::SUPERADMIN, Roles::JEFE];

    private const MENSAJES = [
        'name.required' => 'El nombre del rol es obligatorio.',
        'name.string' => 'El nombre del rol debe ser texto.',
        'name.min' => 'El nombre del rol debe tener al menos 3 letras.',
        'name.max' => 'El nombre del rol no puede superar los 50 caracteres.',
        'name.regex' => 'El nombre del rol solo puede tener letras y espacios.',
        'name.unique' => 'Ya existe un rol con ese nombre.',
        'permissions.array' => 'Los permisos no son válidos.',
        'permissions.*.string' => 'Los permisos no son válidos.',
        'permissions.*.exists' => 'Uno de los permisos seleccionados no existe.',
        'permissions.*.distinct' => 'Hay permisos repetidos.',
    ];

    private static function limpiarNombre(Request $request): void
    {
        if (is_string($request->name)) {
            $request->merge(['name' => preg_replace('/\s+/u', ' ', trim($request->name))]);
        }
    }

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
    self::limpiarNombre($request);

    $request->validate([
        'name' => ['required', 'string', 'min:3', 'max:50', 'regex:/^\p{L}+( \p{L}+)*$/u', 'unique:roles,name'],
        'permissions' => 'nullable|array|max:200',
        'permissions.*' => 'distinct|string|exists:permissions,name',
    ], self::MENSAJES);

    $role = Role::create(['name' => $request->name, 'guard_name' => 'web', 'is_active' => true]);

    if ($request->filled('permissions')) {
        $role->syncPermissions($request->permissions);
    }

    return response()->json(['message' => 'Rol creado correctamente.', 'role' => $role->load('permissions:id,name')], 201);
}

public function update(Request $request, $id)
{
    $role = Role::findOrFail($id);
    self::limpiarNombre($request);

    $request->validate([
        'name' => ['required', 'string', 'min:3', 'max:50', 'regex:/^\p{L}+( \p{L}+)*$/u', Rule::unique('roles', 'name')->ignore($role->id)],
        'permissions' => 'nullable|array|max:200',
        'permissions.*' => 'distinct|string|exists:permissions,name',
    ], self::MENSAJES);

    if (in_array($role->name, self::ROLES_PROTEGIDOS) && $request->name !== $role->name) {
        return response()->json(['message' => 'Este rol no se puede renombrar.'], 422);
    }

    $antes = $role->permissions->pluck('name')->sort()->values()->all();

    $role->name = $request->name;
    $role->save();

    if ($request->has('permissions') && ! in_array($role->name, self::ROLES_TODO_ACCESO)) {
        $role->syncPermissions($request->permissions ?? []);

        $despues = collect($request->permissions ?? [])->sort()->values()->all();
        if ($antes !== $despues) {
            Auditoria::registrar(
                'updated', 'Roles', "Cambió los permisos del rol {$role->name}",
                ['permisos' => implode(', ', $antes) ?: 'Ninguno'],
                ['permisos' => implode(', ', $despues) ?: 'Ninguno']
            );
        }
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