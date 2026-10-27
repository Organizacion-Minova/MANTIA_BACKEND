<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1) Permisos: modulo.accion
        $modulos = [
            'company', 'location', 'location-category',
            'machine', 'machine-category', 'equipment',
            'tool', 'category-tools',
            'gas-measurements',
            'daily-use', 'inspection',
        ];
        $acciones = ['view', 'create', 'update', 'delete'];

        foreach ($modulos as $modulo) {
            foreach ($acciones as $accion) {
                Permission::firstOrCreate(['name' => "{$modulo}.{$accion}"]);
            }
        }

        // 2) Roles. "Administrador" se renombra para no perder sus usuarios.
        Role::where('name', 'Administrador')->update(['name' => 'Jefe de mina']);

        $nombres = [
            'Jefe de mina', 'SST', 'Mantenimiento',
            'Operario mantenimiento', 'Operario gases',
            'Almacenista', 'Visitante',
        ];
        foreach ($nombres as $nombre) {
            Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        // 3) Permisos de cada rol
        $consulta = [ // lo que se necesita para llenar formatos
            'machine.view', 'equipment.view', 'location.view',
            'location-category.view', 'machine-category.view', 'company.view',
        ];
        $formatos = [
            'daily-use.view', 'daily-use.create', 'daily-use.update', 'daily-use.delete',
            'inspection.view', 'inspection.create', 'inspection.update', 'inspection.delete',
        ];

        Role::findByName('Jefe de mina')->syncPermissions(Permission::all());
        Role::findByName('SST')->syncPermissions(Permission::all());

        Role::findByName('Mantenimiento')->syncPermissions(
            array_merge($formatos, $consulta)
        );

        Role::findByName('Operario mantenimiento')->syncPermissions(array_merge([
            'daily-use.view', 'daily-use.create',
            'inspection.view', 'inspection.create',
        ], $consulta));

        Role::findByName('Operario gases')->syncPermissions([
            'gas-measurements.view', 'gas-measurements.create', 'gas-measurements.update',
            'location.view', 'location-category.view',
        ]);

        Role::findByName('Almacenista')->syncPermissions([
            'tool.view', 'tool.create', 'tool.update', 'tool.delete',
            'category-tools.view', 'category-tools.create',
            'category-tools.update', 'category-tools.delete',
        ]);

        Role::findByName('Visitante')->syncPermissions(array_merge([
            'tool.view', 'category-tools.view',
        ], $consulta));

        // 4) Roles viejos: sus usuarios pasan a Visitante y el rol se borra
        $visitante = Role::findByName('Visitante');
        foreach (['Tecnico', 'Recepcionista', 'Invitado'] as $viejo) {
            $rol = Role::where('name', $viejo)->first();
            if (! $rol) {
                continue;
            }
            foreach ($rol->users as $usuario) {
                $usuario->syncRoles([$visitante]);
            }
            $rol->delete();
        }

        // 5) Usuario Jefe de mina. Se busca por dev_letter (es unica).
        $jefe = User::where('dev_letter', 'A')->first();

        if ($jefe) {
            $jefe->update(['email' => 'MantiaCM@gmail.com', 'status' => 'approved']);
        } else {
            $jefe = User::create([
                'email' => 'MantiaCM@gmail.com',
                'name' => 'Administrador Mantia',
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'Mantita123')),
                'status' => 'approved',
                'dev_letter' => 'A',
            ]);
        }

        $jefe->syncRoles(['Jefe de mina']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}