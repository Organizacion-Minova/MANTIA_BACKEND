<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Location::firstOrCreate(
            ['name' => 'Almacén Central'],
            ['description' => 'Ubicación principal de almacenamiento de herramientas y equipos',
            'status' => 'active','location_category_id' => 1]
        );

        Location::firstOrCreate(
            ['name' => 'Taller de Reparación'],
            ['description' => 'Ubicación para la reparación y mantenimiento de herramientas y equipos',
            'status' => 'active','location_category_id' => 2]
        );
    }
}
