<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DevUsersSeeder extends Seeder
{
    public function run(): void
    {
        $equipo = [
            ['dev_letter' => 'D', 'name' => 'Daniela',  'email' => 'daniela@mantia.dev'],
            ['dev_letter' => 'S', 'name' => 'Santiago', 'email' => 'santiago@mantia.dev'],
            ['dev_letter' => 'J', 'name' => 'Joan',     'email' => 'joan@mantia.dev'],
            ['dev_letter' => 'L', 'name' => 'Leonardo', 'email' => 'leonardo@mantia.dev'],
            ['dev_letter' => 'K', 'name' => 'Kevin',    'email' => 'kevin@mantia.dev'],
        ];

        foreach ($equipo as $miembro) {
            User::firstOrCreate(
                ['dev_letter' => $miembro['dev_letter']],
                [
                    'name' => $miembro['name'],
                    'email' => $miembro['email'],
                    // La contraseña nunca se usa (el login es solo por letra),
                    // pero Laravel exige el campo, así que va una al azar.
                    'password' => Hash::make(Str::random(40)),
                ]
            );
        }
    }
}
