<?php

namespace Database\Seeders;
use App\Models\Machine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MachinesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Machine::firstOrCreate(
            ['code' => 'PT2-EL01'],
            [
                'name' => 'Electrobomba 1',
                'model' => '2015',
                'serial_number' => 'SN-001',
                'acquisition_date' => '2022-01-15',
                'acquisition_cost' => 15000.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 1,
                'machine_category_id' => 1,
                'brand'=>'Minova',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Desague',
                'in_operation' => true,
                'characteristics' => 'Potencia: 15HP, Frecuencia: 60HERZ, Voltaje: 220/440 V, Velocidad: 3500 RPM',
            ]
        );
        Machine::firstOrCreate(
            ['code' => 'PT2-EL02'],
            [
                'name' => 'Electrobomba 2',
                'model' => '2016',
                'serial_number' => 'SN-002',
                'acquisition_date' => '2022-02-20',
                'acquisition_cost' => 18000.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 1,
                'machine_category_id' => 1,
                'brand'=>'Minova',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Desague',
                'in_operation' => true,
                'characteristics' => 'Potencia: 20HP, Frecuencia: 60HERZ, Voltaje: 220/440 V, Velocidad: 3500 RPM',
            ]
        );
        Machine::firstOrCreate(
            ['code' => 'PT2-EL03'],
            [
                'name' => 'Electrobomba 3',
                'model' => '2017',
                'serial_number' => 'SN-003',
                'acquisition_date' => '2022-03-25',
                'acquisition_cost' => 20000.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 1,
                'machine_category_id' => 1,
                'brand'=>'Minova',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Desague',
                'in_operation' => true,
                'characteristics' => 'Potencia: 25HP, Frecuencia: 60HERZ, Voltaje: 220/440 V, Velocidad: 3500 RPM',
            ]
        );
        Machine::firstOrCreate(
            ['code' => 'G4C-PU02'],
            [
                'name' => 'Compresor 1',
                'model' => 'CSD 75',
                'serial_number' => 'SST-FT-001',
                'acquisition_date' => '2022-03-25',
                'acquisition_cost' => 20000.00,
                'status' => 'active',
                'warranty' => 12,
                'image' => null,
                'location_id' => 3,
                'machine_category_id' => 1,
                'brand'=>'Minova',
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Para el abastecimiento de aire comprimido',
                'in_operation' => true,
                'characteristics' => 'Fuente de alimentación: Eléctrica con cable 
                                        Marca: Kaeser Compressors
                                        Tensión: 460Y/266 V
                                        Potencia máxima: 23.3 m³/min(825 cfm), 125 HP',
            ]
        );
    }
}

