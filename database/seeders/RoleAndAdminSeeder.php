<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RoleAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Tecnico']);
        Role::firstOrCreate(['name' => 'Recepcionista']);

        // Crear o actualizar un usuario Administrador principal si no existe
        $adminUser = User::firstOrCreate(
            ['email' => 'mantiaadso@gmail.com'],
            [
                'name' => 'Administrador Mantia',
                'password' => Hash::make('password123'),
                'status' => 'approved',
                'dev_letter' => 'A'
            ]
        );

        // Asegurar que esté aprobado y asignarle el rol
        $adminUser->update(['status' => 'approved']);
        $adminUser->assignRole($adminRole);
    }
}
