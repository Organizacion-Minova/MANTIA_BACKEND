<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\InspectionQuestionEquipment;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

class InspectionQuestionEquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipo = Equipment::where('code', 'T1-SO01')->firstOrFail();

        $questions = [
            // Físicos
            '¿El equipo se encuentra en buen estado?',
            '¿Cuenta con pinzas de electrodo?',
            '¿Cuenta con pinzas de cableado?',
            '¿La clavija y el cableado se encuentran en buen estado?',
            '¿El interruptor de encendido y apagado funciona correctamente?',
            '¿La perilla de graduación de corriente gira y funciona correctamente?',
            '¿La señalización lumínica funciona correctamente? (encendido y apagado)',
            '¿La manija de transporte funciona adecuadamente?',

            // Eléctricos
            '¿El sistema de cableado se encuentra en buen estado?',
            '¿La clavija de conexión se encuentra en buen estado?',
            '¿Al mover la perilla de graduación varía la corriente?',
            '¿En operación el equipo presenta fallas?',
            '¿El cableado está aislado correctamente?',
            '¿Los testigos de iluminación funcionan correctamente?',

            // Condiciones de seguridad
            '¿Se realizó la medición de gases dentro de la mina para el uso de la herramienta?',
            'Cuenta con guantes de vaqueta caña media o alta',
            'Cuenta con careta de acrílico - gafas lente claro',
            'Cuenta con peto de vaqueta o carnaza',
            'Cuenta con botas de seguridad punta de acero y polainas',
            'Cuenta con protector auditivo (copa o inserción)',
            'Cuenta con casco de seguridad o protección en la cabeza - capuchón',
            '¿El personal que utiliza el equipo se encuentra capacitado?',
        ];

        foreach ($questions as $i => $text) {
            InspectionQuestionEquipment::updateOrCreate(
                ['equipment_id' => $equipo->id, 'text' => $text],
                [
                    'type'            => 'yes_no_na',
                    'position'        => $i + 1,
                    'is_active'       => true,
                ]
            );
        }
    }
}
