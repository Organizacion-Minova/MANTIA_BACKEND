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
                'name'=>'Transportador blindado ',
                'description'=>'Soldexel   CLASE I  DIV I. GR- D, CLASE  II DIV I .GR.  E.F.G  REF: S7- CE 504035  No. SERIE 11675  TIPO NEMA: 7+4X   PRECAUCION: 
                                para evitar el riesgo de explocion en atmosferas  peligrosas desconecte primero la  caja del circuito de alimenctacion antes de abrir, mantener la 
                                caja cerrada cuando este en operacion. IMPORTANTE: deben deintalarse sellos corta fuegos  a la salidad de las cajas hasta maximo 50 cm 
                                VOLUMEN: (cm)',
                'serial_number'=>'11675',
                'responsible'=>'Técnicos mina',
                'status'=>'active',
                'registration_date'=>'22/08/2023',
                'company_id'=>1,
                'location_id'=>1,
                'image'=>'codes/Equipments/T1-BT02.jpg',
                'model'=>'1960'
            ]

        );
    }
}
