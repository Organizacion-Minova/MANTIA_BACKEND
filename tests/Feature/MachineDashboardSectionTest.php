<?php

namespace Tests\Feature;

use App\Dashboard\SectionState;
use App\Dashboard\Sections\MachinesSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MachineDashboardSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_machine_statuses_after_case_normalization(): void
    {
        $locationCategoryId = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
        ]);
        $locationId = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Ubicación de pruebas',
            'status' => 'active',
            'location_category_id' => $locationCategoryId,
        ]);
        $machineCategoryId = DB::table('machine_category')->insertGetId([
            'name' => 'Maquinaria',
            'description' => 'Categoría de pruebas',
            'status' => 'active',
        ]);

        foreach (['Activo', 'Mantenimiento', 'Inactivo'] as $index => $status) {
            DB::table('machine')->insert([
                'code' => 'M-00'.($index + 1),
                'name' => 'Máquina de prueba '.($index + 1),
                'responsible' => 'Responsable',
                'status' => $status,
                'brand' => 'Marca',
                'plate' => 'P-00'.($index + 1),
                'location_id' => $locationId,
                'machine_category_id' => $machineCategoryId,
            ]);
        }

        $result = (new MachinesSection())->resolve();

        $this->assertSame(SectionState::Ok, $result->state);
        $this->assertSame(3, $result->data['total']);
        $this->assertSame([1, 1, 1], array_column($result->data['breakdown'], 'value'));
    }
}
