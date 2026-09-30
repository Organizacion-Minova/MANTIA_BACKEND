<?php

namespace Database\Seeders;

use App\Models\LocationCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class CategoryLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LocationCategory::firstOrCreate(
            ['name' => 'Almacén'],
            ['description' => 'Ubicación de almacenamiento de herramientas y equipos']
        );
        LocationCategory::firstOrCreate(
            ['name' => 'Taller'],
            ['description' => 'Ubicación de reparación y mantenimiento de herramientas y equipos']
        );
    }
}
