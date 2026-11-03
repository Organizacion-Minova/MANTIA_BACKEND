<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InspectionFormatEquipmentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_update_persists_metadata_sections_and_questions(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createFormatFixture();

        $this->putJson("/api/inspection-formats-equipment/{$fixture['format']}", [
            'code' => 'FORM-02',
            'version' => '02',
            'valid_from' => '2026-11-02',
            'header_fields' => [['key' => 'project', 'label' => 'Proyecto']],
            'signers' => [['key' => 'inspector', 'label' => 'Realiza la inspección']],
            'sections' => [[
                'id' => $fixture['section'],
                'title' => 'Estado actualizado',
                'mode' => 'daily',
                'options' => ['B', 'M'],
                'instructions' => 'Revisar el equipo',
                'has_observation' => true,
                'questions' => [
                    ['id' => $fixture['question'], 'text' => 'Pregunta actualizada'],
                    ['text' => 'Pregunta nueva'],
                ],
            ]],
        ])
            ->assertOk()
            ->assertJsonPath('code', 'FORM-02')
            ->assertJsonPath('version', '02')
            ->assertJsonPath('sections.0.title', 'Estado actualizado')
            ->assertJsonPath('sections.0.questions.0.text', 'Pregunta actualizada')
            ->assertJsonPath('sections.0.questions.1.text', 'Pregunta nueva');

        $this->assertDatabaseHas('inspection_format_equipment', [
            'id' => $fixture['format'],
            'version' => '02',
        ]);
        $this->assertDatabaseHas('inspection_section_equipment', [
            'id' => $fixture['section'],
            'title' => 'Estado actualizado',
        ]);
        $this->assertDatabaseHas('inspection_question_equipment', [
            'id' => $fixture['question'],
            'text' => 'Pregunta actualizada',
        ]);
        $this->assertDatabaseCount('inspection_question_equipment', 2);
    }

    public function test_format_update_rejects_changing_options_when_questions_have_answers(): void
    {
        $this->withoutMiddleware();
        $fixture = $this->createFormatFixture();
        $inspection = DB::table('inspection_equipment')->insertGetId([
            'format_id' => $fixture['format'],
            'week_start' => '2026-11-02',
            'week_end' => '2026-11-08',
        ]);
        DB::table('inspection_answer_equipment')->insert([
            'inspection_id' => $inspection,
            'question_id' => $fixture['question'],
            'day' => 1,
            'value' => 'B',
        ]);

        $this->putJson("/api/inspection-formats-equipment/{$fixture['format']}", [
            'code' => 'FORM-02',
            'version' => '02',
            'sections' => [[
                'id' => $fixture['section'],
                'title' => 'Estado',
                'mode' => 'weekly',
                'options' => ['SI', 'NO'],
                'has_observation' => false,
                'questions' => [['id' => $fixture['question'], 'text' => 'Pregunta']],
            ]],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sections');

        $this->assertDatabaseHas('inspection_format_equipment', [
            'id' => $fixture['format'],
            'version' => '01',
        ]);
        $this->assertDatabaseHas('inspection_section_equipment', [
            'id' => $fixture['section'],
            'mode' => 'daily',
        ]);
    }

    /**
     * @return array{format: int, section: int, question: int}
     */
    private function createFormatFixture(): array
    {
        $locationCategory = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
            'status' => 'active',
        ]);
        $location = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Nivel principal',
            'status' => 'active',
            'location_category_id' => $locationCategory,
        ]);
        $equipment = DB::table('equipment')->insertGetId([
            'code' => 'EQ-001',
            'name' => 'Equipo de prueba',
            'model' => 'Modelo 1',
            'serial_number' => 'SN-001',
            'responsible' => 'Ana',
            'status' => 'active',
            'brand' => 'Marca',
            'location_id' => $location,
            'description' => 'Equipo para pruebas',
        ]);
        $format = DB::table('inspection_format_equipment')->insertGetId([
            'equipment_id' => $equipment,
            'code' => 'FORM-01',
            'version' => '01',
            'is_active' => true,
        ]);
        $section = DB::table('inspection_section_equipment')->insertGetId([
            'format_id' => $format,
            'title' => 'Estado',
            'position' => 1,
            'mode' => 'daily',
            'options' => json_encode(['B', 'M']),
            'has_observation' => false,
        ]);
        $question = DB::table('inspection_question_equipment')->insertGetId([
            'section_id' => $section,
            'text' => 'Pregunta',
            'position' => 1,
            'is_active' => true,
        ]);

        return compact('format', 'section', 'question');
    }
}
