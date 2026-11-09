<?php

namespace Database\Seeders;

use App\Models\InspectionFormatMachine;
use App\Models\InspectionMachine;
use App\Models\Machine;
use App\Models\MachineCategory;
use App\Models\PreoperationalMachine;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MachineRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->preoperacional('%malacate%');
        $this->inspeccion('%electrobomba%');
    }

    // Máquina, formato y preguntas respondibles de una categoría
    private function buscar(string $patron): ?array
    {
        $categoria = MachineCategory::where('name', 'ilike', $patron)->first();
        if (!$categoria) {
            $this->command->warn("No existe una categoría como {$patron}.");
            return null;
        }

        $maquina = Machine::where('machine_category_id', $categoria->id)->first();
        if (!$maquina) {
            $this->command->warn("La categoría {$categoria->name} no tiene ninguna máquina.");
            return null;
        }

        $formato = InspectionFormatMachine::with('sections.questions')
            ->where('category_id', $categoria->id)
            ->where('is_active', true)
            ->first();
        if (!$formato) {
            $this->command->warn("La categoría {$categoria->name} no tiene formato: corre antes InspectionFormatMachineSeeder.");
            return null;
        }

        $preguntas = $formato->sections
            ->flatMap(fn ($s) => $s->questions)
            ->where('is_active', true)
            ->where('type', 'item')
            ->values();

        return [$maquina, $formato, $preguntas];
    }

    // Casi todo "SI", con algunos "NO" y "N/A" repartidos de forma fija
    private function valor(int $preguntaId, int $dia = 0): string
    {
        $n = $preguntaId + $dia;

        if ($n % 23 === 0) {
            return 'NO';
        }
        if ($n % 11 === 0) {
            return 'N/A';
        }
        return 'SI';
    }

    private function preoperacional(string $patron): void
    {
        $datos = $this->buscar($patron);
        if (!$datos) {
            return;
        }
        [$maquina, $formato, $preguntas] = $datos;

        $lunes = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $registro = PreoperationalMachine::firstOrCreate(
            ['machine_id' => $maquina->id, 'week_start' => $lunes->toDateString()],
            [
                'format_id' => $formato->id,
                'week_end' => $lunes->copy()->addDays(6)->toDateString(),
                'inspector' => 'Inspector de prueba',
                'area' => 'Mina didáctica',
                'duration_minutes' => 30,
                'reviewed_by' => 'Jefe de área',
                'review_date' => now()->toDateString(),
                'general_observations' => 'Registro de prueba generado por el seeder.',
                'signature' => null,
            ]
        );

        if (!$registro->wasRecentlyCreated) {
            $this->command->info("{$maquina->name}: ya tiene el preoperacional de esta semana.");
            return;
        }

        $hasta = now()->dayOfWeekIso;   // 1 = lunes ... 7 = domingo: se marca hasta hoy
        $filas = [];
        foreach ($preguntas as $q) {
            for ($dia = 1; $dia <= $hasta; $dia++) {
                $filas[] = ['question_id' => $q->id, 'day' => $dia, 'value' => $this->valor($q->id, $dia)];
            }
        }

        $registro->answers()->createMany($filas);
    }

    private function inspeccion(string $patron): void
    {
        $datos = $this->buscar($patron);
        if (!$datos) {
            return;
        }
        [$maquina, $formato, $preguntas] = $datos;

        $registro = InspectionMachine::firstOrCreate(
            ['machine_id' => $maquina->id, 'inspection_date' => now()->toDateString()],
            [
                'format_id' => $formato->id,
                'inspector' => 'Inspector de prueba',
                'area' => 'Mina didáctica',
                'duration_minutes' => 45,
                'reviewed_by' => 'Jefe de área',
                'review_date' => now()->toDateString(),
                'general_observations' => 'Inspección de prueba generada por el seeder.',
                'signature' => null,
            ]
        );

        if (!$registro->wasRecentlyCreated) {
            $this->command->info("{$maquina->name}: ya tiene la inspección de hoy.");
            return;
        }

        $filas = [];
        foreach ($preguntas as $q) {
            $valor = $this->valor($q->id);
            $filas[] = [
                'question_id' => $q->id,
                'value' => $valor,
                'observation' => $valor === 'NO' ? 'Se recomienda revisar y programar mantenimiento.' : null,
            ];
        }

        $registro->answers()->createMany($filas);
    }
}
