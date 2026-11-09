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
            ['name' => 'Electrobombas'],
            [
                'description' => 'Impulsado por un motor eléctrico que transforma la energía',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Compresores'],
            [
                'description' => 'Toman el aire del ambiente, reduce su volumen y aumenta su presión',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name' => 'Ventiladores'],
            [
                'description' => 'Impulsa grandes volúmenes de aire a baja presión',
                'status' => 'active'
            ]
        );

        MachineCategory::firstOrCreate(
            ['name'=>'Malacates'],
            [
                'description'=>'Se utiliza para levantar o arrastrar cargas pesadas mediante un cable enrollado en un tambor',
                'status'=>'active'
            ]
        );
    }
}