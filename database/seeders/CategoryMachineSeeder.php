<?php

namespace Database\Seeders;

use App\Models\MachineCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryMachineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MachineCategory::firstOrCreate(
            ['name' => 'Motores'],
            [
                'description' => 'Motores eléctricos e industriales',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Bombas'],
            [
                'description' => 'Bombas hidráulicas, eléctricas y equipos de bombeo',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Compresores'],
            [
                'description' => 'Equipos destinados a generar y suministrar aire comprimido',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Generadores'],
            [
                'description' => 'Equipos utilizados para la generación de energía eléctrica',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Equipos de ventilación'],
            [
                'description' => 'Maquinaria destinada a la circulación y renovación del aire en espacios mineros',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Equipos de perforación'],
            [
                'description' => 'Maquinaria utilizada para perforar roca, suelo y otros materiales',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Equipos de trituración'],
            [
                'description' => 'Maquinaria destinada a reducir el tamaño de minerales y materiales sólidos',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Equipos de transporte'],
            [
                'description' => 'Maquinaria utilizada para transportar materiales y minerales',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Equipos de elevación'],
            [
                'description' => 'Maquinaria destinada a levantar y movilizar cargas pesadas',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Equipos de construcción'],
            [
                'description' => 'Maquinaria utilizada en trabajos de construcción, adecuación y mantenimiento de infraestructura',
                'status' => 'active'
            ]
        );
    }
}
