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
        ['date' => '2023-08-23'],
        [
            'operating_status' => 'Si',
            'operation_start' => '07:00',
            'operation_end' => '16:00',
            'responsible' => 'Técnicos mina',
            'observations' => 'Funcionamiento normal durante la jornada.',
            'machine_id' => 2
        ]
    );

    DailyUse::firstOrCreate(
        ['date' => '2023-08-24'],
        [
            'operating_status' => 'Si',
            'operation_start' => '08:00',
            'operation_end' => '17:00',
            'responsible' => 'Jefe de mina',
            'observations' => 'Se realizó inspección general antes de iniciar operaciones.',
            'machine_id' => 2
        ]
    );

    DailyUse::firstOrCreate(
        ['date' => '2023-08-25'],
        [
            'operating_status' => 'No',
            'operation_start' => null,
            'operation_end' => null,
            'responsible' => 'Técnicos mina',
            'observations' => 'Equipo fuera de servicio por mantenimiento preventivo.',
            'machine_id' => 2
        ]
    );

    DailyUse::firstOrCreate(
        ['date' => '2023-08-26'],
        [
            'operating_status' => 'Si',
            'operation_start' => '06:30',
            'operation_end' => '15:30',
            'responsible' => 'Técnicos mina',
            'observations' => 'Operación estable sin novedades.',
            'machine_id' => 2
        ]
    );

        DailyUse::firstOrCreate(
            ['date' => '2023-08-27'],
            [
                'operating_status' => 'Si',
                'operation_start' => '09:00',
                'operation_end' => '18:00',
                'responsible' => 'Jefe de mina',
                'observations' => 'Se verificaron las condiciones eléctricas del equipo.',
                'machine_id' => 2
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

        DailyUse::firstOrCreate(
        ['date' => '2023-08-28'],
        [
        'operating_status' => 'Si',
        'operation_start' => '07:00',
        'operation_end' => '16:00',
        'responsible' => 'Técnicos mina',
        'observations' => 'Equipo operando correctamente.',
        'machine_id' => 3
     ]
    );

    DailyUse::firstOrCreate(
        ['date' => '2023-08-29'],
        [
            'operating_status' => 'Si',
            'operation_start' => '08:00',
            'operation_end' => '17:00',
            'responsible' => 'Operador de maquinaria',
            'observations' => 'Se realizó revisión de componentes antes de su uso.',
            'machine_id' => 3
        ]
    );

    DailyUse::firstOrCreate(
        ['date' => '2023-08-30'],
        [
            'operating_status' => 'No',
            'operation_start' => null,
            'operation_end' => null,
            'responsible' => 'Técnicos mina',
            'observations' => 'Equipo detenido por revisión técnica.',
            'machine_id' => 3
        ]
    );

    DailyUse::firstOrCreate(
        ['date' => '2023-08-31'],
        [
            'operating_status' => 'Si',
            'operation_start' => '06:00',
            'operation_end' => '15:00',
            'responsible' => 'Operador de maquinaria',
            'observations' => 'Funcionamiento continuo durante la jornada.',
            'machine_id' => 3
        ]
    );

    DailyUse::firstOrCreate(
        ['date' => '2023-09-01'],
        [
            'operating_status' => 'Si',
            'operation_start' => '08:30',
            'operation_end' => '17:30',
            'responsible' => 'Técnicos mina',
            'observations' => 'Se finalizó la jornada sin reportar inconvenientes.',
            'machine_id' => 3
        ]
    );
    }
}
