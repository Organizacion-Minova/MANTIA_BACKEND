<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Equipment;
use App\Models\InspectionEquipment;
use App\Models\InspectionQuestionEquipment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EquipmentInspectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipment = Equipment::where('code', 'T1-SO01')->firstOrFail();

        $questions = InspectionQuestionEquipment::where('equipment_id', $equipment->id)
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        if ($questions->isEmpty()) {
            $this->command->warn('El equipo no tiene preguntas. Corre primero el seeder de preguntas.');
            return;
        }

        DB::transaction(function () use ($equipment, $questions) {
            // firstOrCreate: si corres el seeder dos veces no duplica la inspección
            $inspection = InspectionEquipment::firstOrCreate(
                [
                    'equipment_id'    => $equipment->id,
                    'inspection_date' => '2026-10-26',
                    'serial_number'   => 'ABC123',
                ],
                [
                    'inspector_name'         => 'Juan Pérez',
                    'inspection_time'        => '08:30',
                    'equipment_brand'        => 'Lincoln',
                    'voltage'                => '220V',
                    'area'                   => 'Mina nivel 2',
                    'general_observations'   => 'Inspección de ejemplo generada por seeder.',
                    'responsible_position'   => 'Supervisor de mantenimiento',
                    'warehouse_manager_name' => 'María Gómez',
                    // firmas: null (son archivos)
                ]
            );

            foreach ($questions as $question) {
                // Valores por defecto: todo bien
                $answer      = 'yes';
                $observation = null;

                // La pregunta de fallas tiene lógica invertida: "no" es lo correcto
                if (str_contains($question->text, 'presenta fallas')) {
                    $answer = 'no';
                }

                // Un par de casos para que los datos de prueba tengan variedad
                if (str_contains($question->text, 'pinzas de cableado')) {
                    $answer      = 'no';
                    $observation = 'Falta una pinza, se solicitó reposición.';
                }
                if (str_contains($question->text, 'protector auditivo')) {
                    $answer = 'na';
                }

                $inspection->answers()->updateOrCreate(
                    ['question_id' => $question->id],
                    ['answer' => $answer, 'observation' => $observation]
                );
            }
        });

    }
}
