<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\InspectionEquipment;
use App\Models\InspectionFormatEquipment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InspectionEquipmentRecordSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = Equipment::where('code', 'T1-MN01')->first();

        if (!$equipment) {
            $this->command->warn('No existe el equipo T1-MN01. Créalo y vuelve a correr el seeder.');
            return;
        }

        $format = InspectionFormatEquipment::with('sections.questions')
            ->where('equipment_id', $equipment->id)
            ->where('is_active', true)
            ->first();

        if (!$format) {
            $this->command->warn("El equipo {$equipment->code} no tiene un formato de inspección activo.");
            return;
        }

        $currentWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

        foreach ([$currentWeek, $currentWeek->copy()->subWeek()] as $weekIndex => $weekStart) {
            $weekEnd = $weekStart->copy()->addDays(6);
            $inspection = InspectionEquipment::updateOrCreate(
                ['format_id' => $format->id, 'week_start' => $weekStart->toDateString()],
                [
                    'week_end' => $weekEnd->toDateString(),
                    'header_data' => ['project' => 'Proyecto de prueba'],
                    'general_observations' => 'Inspección de prueba generada por el seeder.',
                ]
            );

            foreach ($format->sections as $section) {
                $days = $section->mode === 'weekly'
                    ? [0]
                    : range(1, $weekIndex === 0 ? Carbon::now()->dayOfWeekIso : 7);

                foreach ($section->questions->where('is_active', true) as $question) {
                    foreach ($days as $day) {
                        $options = $section->options;
                        $value = $options[($question->id + $weekIndex + $day) % count($options)];

                        $inspection->answers()->updateOrCreate(
                            ['question_id' => $question->id, 'day' => $day],
                            [
                                'value' => $value,
                                'observation' => $section->has_observation && in_array($value, ['NC', 'M', 'NO'], true)
                                    ? 'Se recomienda revisar y programar mantenimiento.'
                                    : null,
                            ]
                        );
                    }
                }
            }

            foreach ($format->signers ?? [] as $signer) {
                $inspection->signatures()->updateOrCreate(
                    ['role' => $signer['key']],
                    [
                        'name' => 'Firmante de prueba',
                        'id_number' => null,
                        'signature' => null,
                    ]
                );
            }
        }

        $this->command->info("Se sembraron dos inspecciones para el equipo {$equipment->code}.");
    }
}
