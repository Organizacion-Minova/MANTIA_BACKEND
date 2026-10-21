<?php

namespace Database\Seeders;

use App\Models\DailyUse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DailyUseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DailyUse::firstOrCreate(
            ['date'=>'2023-08-22'],
            [
                'operating_status'=>'Si',
                'operation_start'=>'08:00',
                'operation_end'=>'17:00',
                'responsible'=>'Técnicos mina',
                'observations'=>'Ninguna',
                'machine_id'=>1
            ]
        );
        DailyUse::firstOrCreate(
            ['date'=>'2023-08-23'],
            [
                'operating_status'=>'No',
                'operation_start'=>null,
                'operation_end'=>null,
                'responsible'=>'Técnicos mina',
                'observations'=>'Mantenimiento preventivo',
                'machine_id'=>2
            ]
        );
        DailyUse::firstOrCreate(
            ['date'=>'2023-08-24'],
            [
                'operating_status'=>'Si',
                'operation_start'=>'08:00',
                'operation_end'=>'17:00',
                'responsible'=>'Técnicos mina',
                'observations'=>'Ninguna',
                'machine_id'=>2
            ]
        );
        DailyUse::firstOrCreate(
            ['date'=>'2023-08-25'],
            [
                'operating_status'=>'Si',
                'operation_start'=>'08:00',
                'operation_end'=>'17:00',
                'responsible'=>'Técnicos mina',
                'observations'=>'Ninguna',
                'machine_id'=>3
            ]
        );
    }
}
