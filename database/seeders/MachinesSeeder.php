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
                'company_id' => 1,
                'responsible' => 'Jefe de mina',
                'machine_usage' => 'Desague',
                'in_operation' => true,
                'characteristics' => 'Potencia: 15HP, Frecuencia: 60HERZ, Voltaje: 220/440 V, Velocidad: 3500 RPM',
            ]
        );
    }
}
