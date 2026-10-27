<?php

namespace Database\Seeders;

use App\Models\InspectionQuestion;
use App\Models\Machine;
use Illuminate\Database\Seeder;

class InspectionQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $maquina = Machine::where('code', 'G4C-PU02')->first();

        if (!$maquina) {
            $this->command->warn('No existe esta maquina');
            return;
        }

        $preguntas = [
            // A. Verificación del estado de la máquina
            '1.1 Tornillería y tuercas',
            '1.2 Revisión de la carcasa o estructura metálica',
            '2.1 Sistema de succión',
            '2.2 Válvula check de apertura',
            '2.3 Abrazaderas',
            '2.4 Filtro',
            '2.5 Accesorios y racores',
            '2.6 Ventilador',
            '3.1 Voltaje del motor (440V ± 2%)',
            '3.2 Eje de motor',
            '3.3 Carcasa de motor',
            '3.4 Caja de bornera de motor',
            '3.5 Conexiones en buen estado',
            '4.1 Accesorios (niples, tapón, adaptadores roscados y abrazaderas)',
            '4.2 Válvulas de presión',
            '4.3 Manguera',
            '4.4 Racores',
            '4.5 Bujes',
            '4.6 Tubería galvanizada',
            '4.7 Accesorios (uniones, universales)',
            '4.8 Soportería y anclaje de tubería galvanizada',
            '5.1 Cableado desde tablero eléctrico hasta compresor',
            '5.2 Ductos de cableado',
            '6.1 Mandos de marcha y parada del compresor',
            '6.2 Contactor del mando de marcha y parada',
            '6.4 Conexiones en el compresor y tablero eléctrico',
            '6.5 Transformador 440V-220V',
            '6.6 Protección principal del compresor',
            '6.7 Protección principal del tablero eléctrico',
            '6.8 Tablero eléctrico principal',
            '7.1 Nivel y calidad de aceite',
            '7.2 Empalmes y conexiones',
            '7.3 Sensor de temperatura',
            '7.4 Sensor de mantenimiento',
            '7.5 Tubería a secador',
            '7.6 Empaques',

            // B. Señalización
            'B.1 ¿Se encuentra con señalización adecuada?',
            'B.2 ¿Se encuentran señaladas las áreas de riesgo eléctrico?',
            'B.3 ¿Se evidencia señalización de operación?',
            'B.4 ¿Se encuentra señalizado con sus riesgos?',

            // C. Condiciones de utilización del equipo
            'C.1 ¿Se cuenta con todos los EPPs para la operación del compresor?',
            'C.2 ¿El área de trabajo se encuentra despejada y limpia?',
            'C.3 ¿Se avisa a personas diferentes a la actividad la operación del compresor?',
            'C.4 ¿La operación del equipo se hace por personal capacitado o bajo la supervisión de este?',
        ];

        foreach ($preguntas as $indice => $texto) {
            InspectionQuestion::firstOrCreate(
                ['machine_id' => $maquina->id, 'text' => $texto],
                [
                    'type' => 'yes_no_na',
                    'position' => $indice + 1,
                    'is_active' => true,
                ]
            );
        }
    }

}
