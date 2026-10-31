<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Equipment;
use App\Models\InspectionFormatEquipment;
use App\Models\InspectionQuestionEquipment;
use App\Models\InspectionSectionEquipment;
use Illuminate\Database\Seeder;

class InspectionFormatEquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->crearFormato('T1-MN01', [
            'version' => '1',
            'valid_from' => '2026-05-08',
            'header_fields' => [['key' => 'project', 'label' => 'Proyecto']],
            'signers' => [
                ['key' => 'inspector', 'label' => 'Realiza la inspección'],
                ['key' => 'reviewer', 'label' => 'Revisó (Vo.Bo.)'],
            ],
            'sections' => [
                [
                    'title' => 'Estado mecánico',
                    'mode' => 'daily',
                    'options' => ['C', 'NC', 'N/A'],
                    'instructions' => 'Marque C (cumple), NC (no cumple) o N/A (no aplica)',
                    'has_observation' => true,
                    'questions' => [
                        'Conexión neumática sellada',
                        'Pin para acople ajustado',
                        'Manguera sin abolladuras ni ruptura',
                        'Unidad de lubricación instalada correctamente',
                        'Punta rompedora bien instalada',
                        'Manijas de agarre en buen estado',
                        'Manijas bien sujetas al equipo',
                        'Salida de gases sin obstrucción',
                        'Estado de la plaqueta de identificación',
                        'Estado general del martillo',
                    ],
                ],
                [
                    'title' => 'Factores humanos',
                    'mode' => 'weekly',
                    'options' => ['SI', 'NO'],
                    'instructions' => 'Se evalúa semanalmente por el Jefe Inmediato o SST de Campo',
                    'has_observation' => true,
                    'questions' => [
                        '¿Cumple con las instrucciones de operatividad del equipo?',
                        'El operador usa de manera adecuada los EPP cuando está haciendo uso del equipo',
                        'El operador reporta actos y condiciones inseguras / seguras',
                    ],
                ],
            ],
        ]);

        $this->crearFormato('T1-TA01', [
            'code' => 'HSE-F-035',
            'version' => '01',
            'valid_from' => '2026-05-26',
            'header_fields' => [
                ['key' => 'area', 'label' => 'Área'],
                ['key' => 'work_group', 'label' => 'Grupo de trabajo'],
            ],
            'signers' => [
                ['key' => 'inspector', 'label' => 'Realiza la inspección'],
                ['key' => 'reviewer', 'label' => 'Revisó (Vo.Bo.)'],
            ],
            'sections' => [
                [
                    'title' => 'Descripción de taladro',
                    'mode' => 'daily',
                    'options' => ['B', 'M'],
                    'has_observation' => false,
                    'questions' => [
                        'Se verificó el estado de conexiones eléctricas (extensiones, cables, toma)',
                        'Estado de instalación de la broca (inserción)',
                        'Cuenta con acoples adecuados para los accesorios',
                        'Estado e instalación del mango',
                        'Estado del interruptor de incendio y su seguro',
                        'Estado del cable de alimentación',
                        'Se utilizan accesorios apropiados para la tarea',
                        'Estado general del taladro (fisuras, roturas, aseo, etc.)',
                        'Se utilizan adecuadamente los EPP para la labor',
                        'Se han instalado barreras y/o aislamientos apropiados',
                        'Estado de las brocas',
                    ],
                ],
            ],
        ]);

        $this->crearFormato('T1-PU01', [
            'code' => 'HSE-F-035',
            'version' => '01',
            'valid_from' => '2026-05-26',
            'header_fields' => [
                ['key' => 'area', 'label' => 'Área'],
                ['key' => 'work_group', 'label' => 'Grupo de trabajo'],
            ],
            'signers' => [
                ['key' => 'inspector', 'label' => 'Realiza la inspección'],
                ['key' => 'collaborator', 'label' => 'Colaborador'],
                ['key' => 'reviewer', 'label' => 'Revisó (Vo.Bo.)'],
            ],
            'sections' => [
                [
                    'title' => 'Descripción de pulidora',
                    'mode' => 'daily',
                    'options' => ['B', 'M'],
                    'has_observation' => false,
                    'questions' => [
                        'Se verificó el estado de conexiones eléctricas (extensiones, cables, toma)',
                        'Estado de instalación del disco (inserción)',
                        'Cuenta con acoples adecuados para los accesorios',
                        'Estado e instalación del mango',
                        'Estado del interruptor de incendio y su seguro',
                        'Estado de la guía de la guarda',
                        'Se utilizan accesorios apropiados para las RPM',
                        'Se utilizan accesorios apropiados para la tarea',
                        'Estado general de la pulidora (fisuras, roturas, aseo, etc.)',
                        'Se utilizan adecuadamente los EPP para la tarea',
                        'Se han instalado barreras y/o aislamientos apropiados',
                        'Estado del disco',
                    ],
                ],
            ],
        ]);
    }
    private function crearFormato(string $patronEquipo, array $datos): void
    {
        $equipo = Equipment::where('code', 'ilike', $patronEquipo)->first();

        if (!$equipo) {
            $this->command->warn("No existe un equipo como {$patronEquipo}. Créalo y vuelve a correr el seeder.");
            return;
        }

        $formato = InspectionFormatEquipment::updateOrCreate(
            ['equipment_id' => $equipo->id, 'version' => $datos['version']],
            [
                'code' => $datos['code'] ?? null,
                'valid_from' => $datos['valid_from'],
                'header_fields' => $datos['header_fields'],
                'signers' => $datos['signers'] ?? null,
                'is_active' => true,
            ]
        );

        foreach ($datos['sections'] as $i => $s) {
            $seccion = InspectionSectionEquipment::updateOrCreate(
                ['format_id' => $formato->id, 'title' => $s['title']],
                [
                    'position' => $i + 1,
                    'mode' => $s['mode'],
                    'options' => $s['options'],
                    'instructions' => $s['instructions'] ?? null,
                    'has_observation' => $s['has_observation'],
                ]
            );

            foreach ($s['questions'] as $j => $texto) {
                InspectionQuestionEquipment::updateOrCreate(
                    ['section_id' => $seccion->id, 'text' => $texto],
                    ['position' => $j + 1, 'is_active' => true]
                );
            }
    }
}}