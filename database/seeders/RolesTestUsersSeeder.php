<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Un usuario de prueba por rol. SOLO corre en entorno local.
 * Login rápido por letra (igual que el admin "A") o con correo + Prueba1234.
 */
class RolesTestUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('Solo se ejecuta en entorno local.');
            return;
        }

        $usuarios = [
            ['B', 'Prueba SST',                    'sst@mantia.test',            'SST'],
            ['C', 'Prueba Mantenimiento',          'mantenimiento@mantia.test',  'Mantenimiento'],
            ['E', 'Prueba Operario Mant.',         'op.mant@mantia.test',        'Operario mantenimiento'],
            ['F', 'Prueba Operario Gases',         'op.gases@mantia.test',       'Operario gases'],
            ['G', 'Prueba Almacenista',            'almacen@mantia.test',        'Almacenista'],
            ['H', 'Prueba Visitante',              'visitante@mantia.test',      'Visitante'],
        ];

        foreach ($usuarios as [$letra, $nombre, $email, $rol]) {
            $user = User::where('dev_letter', $letra)->orWhere('email', $email)->first()
                ?? new User();

            $user->forceFill([
                'name' => $nombre,
                'email' => $email,
                'password' => Hash::make('Prueba1234'),
                'status' => 'approved',
                'dev_letter' => $letra,
            ])->save();

            $user->syncRoles([$rol]);
        }
    }
}
