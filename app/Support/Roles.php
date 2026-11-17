<?php

namespace App\Support;

use App\Models\User;

/** Nombres de los roles que el sistema trata de forma especial. */
class Roles
{
    public const SUPERADMIN = 'Superadministrador';
    public const JEFE = 'Jefe de mina';
    public const VISITANTE = 'Visitante';

    // Entran al panel de administración
    public const ADMINISTRADORES = [self::SUPERADMIN, self::JEFE];

    // No se pueden renombrar, desactivar, eliminar ni cambiar sus permisos
    public const PROTEGIDOS = [self::SUPERADMIN, self::JEFE, self::VISITANTE];

    // Lo que hacen estos roles no lo ve el Jefe de mina en el historial
    public const OCULTOS_PARA_JEFE = [self::SUPERADMIN, self::VISITANTE];

    public static function esSuperadmin(mixed $usuario): bool
    {
        if (! $usuario instanceof User) {
            return false;
        }

        return (bool) $usuario->roles->where('is_active', true)->contains('name', self::SUPERADMIN);
    }

    public static function esAdministrador(mixed $usuario): bool
    {
        if (! $usuario instanceof User) {
            return false;
        }

        return (bool) $usuario->roles->where('is_active', true)->whereIn('name', self::ADMINISTRADORES)->isNotEmpty();
    }

    /** Rol con el que se muestra al usuario (el de más jerarquía si tuviera varios). */
    public static function principal(?User $usuario): ?string
    {
        if (! $usuario) {
            return null;
        }

        $nombres = $usuario->roles->pluck('name');

        foreach (self::ADMINISTRADORES as $rol) {
            if ($nombres->contains($rol)) {
                return $rol;
            }
        }

        return $nombres->first();
    }

    /** Usuario con sus roles ACTIVOS y la lista de permisos (el Superadministrador recibe todos). */
    public static function usuarioConPermisos(User $usuario): User
    {
        // Solo cuentan los roles ACTIVOS: un rol desactivado no da permisos
        $user = $usuario->load(['roles' => fn ($q) => $q->where('is_active', true)->with('permissions')]);

        $permisos = self::esSuperadmin($user)
            ? \Spatie\Permission\Models\Permission::pluck('name')
            : $user->roles
                ->flatMap(fn ($rol) => $rol->permissions->pluck('name'))
                ->merge($user->getDirectPermissions()->pluck('name'));

        $user->roles->each(fn ($rol) => $rol->unsetRelation('permissions'));
        $user->setAttribute('permission_names', $permisos->unique()->values());

        return $user;
    }
}
