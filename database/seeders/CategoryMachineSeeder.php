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
            ['name' => 'Compresores'],
            ['description' => 'Máquinas de compresión de aire'],
            ['status' => 'activo']

        );

        MachineCategory::firstOrCreate(
            ['name' => 'Motores'],
            ['description' => 'Motores eléctricos e industriales'],
            ['status' => 'activo']
        );
    }
}
