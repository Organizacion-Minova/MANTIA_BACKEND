<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EquipmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Equipment::firstOrCreate(
            ['code'=>'T1-BT02'],
            [
                'name'=>'PLANTA ELECTRICA  ',
                'model'=>'6BT5. 9-62',
                'serial_number'=>'78223950',
                'responsible'=>'Técnicos mina',
                'status'=>'active',
                'start_up_date'=>'22/08/2023',
                'brand'=>'Dongfeng Cummins',
                'plate'=>'9999',
                'fuel'=>'Diesel',
                'power'=>'86 a 1500  r/min, 100 a 1800 r/ min, 92 a 1500 r/min, 110 a 1800 r/min ',
                'voltage'=>'nn',
                'location_id'=>1,
                'image'=>'codes/Equipments/T1-BT02.jpg',
                'description'=>'DESPLAZAMIENTO: 5.9 L
                                PESO: 411 KG
                                LIMITE DE ALTITUD: M
                                CLP:  3076
                                RELANTI: 800 R/ MIN  
                                TASA DE COMBUSTIBLE : 85 mm/',
                
            ]

        );
        Equipment::firstOrCreate(
            ['code' => 'T1-SO01',],
            [
                'name' => 'Equipo de soldadura',
                'model' => 'POR_DEFINIR',
                'serial_number' => 'POR_DEFINIR',
                'responsible' => 'Técnicos mina',
                'status' => 'active',
                'start_up_date' => '22/08/2023',
                'brand' => 'Minova',
                'plate' => '5565895',
                'fuel' => 'Electrodos de soldadura',
                'power' => '7.5 kW',
                'voltage' => '220 V',
                'location_id' => 1,
                'image' => 'codes/Equipments/T1-SO01.jpg',
                'description' => 'Equipo de soldadura utilizado para realizar trabajos de mantenimiento y reparación de maquinaria minera.',
            ]
        );
    }
}
