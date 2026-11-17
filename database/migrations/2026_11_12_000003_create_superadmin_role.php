<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Crea el rol "Superadministrador" con todos los permisos.
 * Para asignárselo a una cuenta: php artisan mantia:superadmin correo@ejemplo.com
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $ahora = now();
        $rolId = DB::table('roles')->where('name', 'Superadministrador')->where('guard_name', 'web')->value('id');

        if (! $rolId) {
            $rolId = DB::table('roles')->insertGetId([
                'name' => 'Superadministrador',
                'guard_name' => 'web',
                'is_active' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        $permisos = DB::table('permissions')->where('guard_name', 'web')->pluck('id');
        foreach ($permisos as $permisoId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permisoId,
                'role_id' => $rolId,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $rolId = DB::table('roles')->where('name', 'Superadministrador')->value('id');

        if ($rolId) {
            DB::table('role_has_permissions')->where('role_id', $rolId)->delete();
            DB::table('model_has_roles')->where('role_id', $rolId)->delete();
            DB::table('roles')->where('id', $rolId)->delete();
        }
    }
};
