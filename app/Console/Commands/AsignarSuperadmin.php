<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Da el rol Superadministrador a una cuenta existente (solo desde la terminal del servidor).
 * Uso: php artisan mantia:superadmin correo@ejemplo.com
 */
class AsignarSuperadmin extends Command
{
    protected $signature = 'mantia:superadmin {email : Correo de la cuenta que será Superadministrador}';

    protected $description = 'Asigna el rol Superadministrador (acceso completo) a una cuenta existente';

    public function handle(): int
    {
        $usuario = User::whereRaw('lower(email) = ?', [mb_strtolower(trim($this->argument('email')))])->first();

        if (! $usuario) {
            $this->error('No existe una cuenta con ese correo. Créala primero (registro o panel de administración).');

            return self::FAILURE;
        }

        $rol = Role::firstOrCreate(['name' => Roles::SUPERADMIN, 'guard_name' => 'web'], ['is_active' => true]);
        $rol->syncPermissions(Permission::all());

        $usuario->forceFill(['status' => 'approved', 'is_active' => true, 'email_verified_at' => $usuario->email_verified_at ?? now()])->save();
        $usuario->syncRoles([$rol]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("{$usuario->email} ahora es Superadministrador.");

        return self::SUCCESS;
    }
}
