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

        Location::firstOrCreate(
            ['name' => 'Salón de Compresores'],
            ['description' => 'Ubicacion donde estan ubicados los compresosres',
             'status'=>'active','location_category_id' => 2]
        );

        Location::firstOrCreate(
            ['name' => 'Tunel 1'],
            ['description' => 'Ubicación de Tunel 1',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Tunel 2'],
            ['description' => 'Ubicación de Tunel 2',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Tunel 3'],
            ['description' => 'Ubicación de Tunel 3',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Cruzada de galeria 1'],
            ['description' => 'Ubicación de Cruzada de galeria 1',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Galeria 2 centro'],
            ['description' => 'Ubicación de Galeria 2 centro',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Galeria 2 norte'],
            ['description' => 'Ubicación de Galeria 2 norte',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Galeria 2 sur'],
            ['description' => 'Ubicación de Galeria 2 sur',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Transversal norte'],
            ['description' => 'Ubicación de Transversal norte',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Transversal sur'],
            ['description' => 'Ubicación de Transversal sur',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Galeria 3 centro'],
            ['description' => 'Ubicación de Galeria 3 centro',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Galeria 3 sur'],
            ['description' => 'Ubicación de Galeria 3 sur',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Galeria 3 norte'],
            ['description' => 'Ubicación de Galeria 3 norte',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Cruzada de galeria 33'],
            ['description' => 'Ubicación de Cruzada de galeria 33',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Diagonal norte'],
            ['description' => 'Ubicación de Diagonal norte',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Diagonal sur'],
            ['description' => 'Ubicación de Diagonal sur',
            'status' => 'active','location_category_id' => 3]
        );

        Location::firstOrCreate(
            ['name' => 'Galeria 4'],
            ['description' => 'Ubicación de Galeria 4',
            'status' => 'active','location_category_id' => 3]
        );
    }
}
