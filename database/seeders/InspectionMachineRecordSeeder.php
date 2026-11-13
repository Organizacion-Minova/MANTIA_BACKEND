<?php

namespace Database\Seeders;

use App\Models\InspectionFormatMachine;
use App\Models\InspectionMachine;
use App\Models\Machine;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InspectionMachineRecordSeeder extends Seeder
{
    public function run(): void
    {
        $machine = Machine::where('code', 'PT2-EL01')->first();

        if (!$machine) {
            $this->command->warn('No existe la máquina PT2-EL01. Créala y vuelve a correr el seeder.');
            return;
        }

        $format = InspectionFormatMachine::with('sections.questions')
            ->where('category_id', $machine->machine_category_id)
            ->where('is_active', true)
            ->first();

        if (!$format) {
            $this->command->warn("La categoría de {$machine->code} no tiene un formato de inspección activo.");
            return;
        }

        foreach ([Carbon::today(), Carbon::yesterday()] as $inspectionDate) {
            $inspection = InspectionMachine::updateOrCreate(
                ['machine_id' => $machine->id, 'inspection_date' => $inspectionDate->toDateString()],
                [
                    'format_id' => $format->id,
                    'inspector' => 'Inspector de prueba',
                    'area' => 'Mina didáctica',
                    'duration_minutes' => 45,
                    'reviewed_by' => 'Jefe de área',
                    'review_date' => $inspectionDate->toDateString(),
                    'general_observations' => 'Inspección de prueba generada por el seeder.',
                    'signature' => null,
                ]
            );

            foreach ($format->sections as $section) {
                foreach ($section->questions->where('is_active', true)->where('type', 'item') as $question) {
                    $value = ($question->id + $inspectionDate->day) % 23 === 0
                        ? 'NO'
                        : (($question->id + $inspectionDate->day) % 11 === 0 ? 'N/A' : 'SI');

                    $inspection->answers()->updateOrCreate(
                        ['question_id' => $question->id],
                        [
                            'value' => $value,
                            'observation' => $value === 'NO'
                                ? 'Se recomienda revisar y programar mantenimiento.'
                                : null,
                        ]
                    );
                }
            }
        }

        $this->command->info("Se sembraron dos inspecciones para la máquina {$machine->code}.");
    }
}
