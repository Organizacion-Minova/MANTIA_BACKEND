<?php

namespace Tests\Feature;

use App\Dashboard\SectionState;
use App\Dashboard\Sections\EquipmentsConsumablesSection;
use App\Dashboard\Sections\EquipmentsNonConsumablesSection;
use App\Models\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EquipmentDashboardSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_summarizes_equipment_categories_separately(): void
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

        $this->createEquipment($locationId, Equipment::CATEGORY_CONSUMABLE, 'Activo', 1);
        $this->createEquipment($locationId, Equipment::CATEGORY_CONSUMABLE, 'Mantenimiento', 2);
        $this->createEquipment($locationId, Equipment::CATEGORY_NON_CONSUMABLE, 'Inactivo', 3);

        $consumables = (new EquipmentsConsumablesSection())->resolve();
        $nonConsumables = (new EquipmentsNonConsumablesSection())->resolve();

        $this->assertSame(SectionState::Ok, $consumables->state);
        $this->assertSame(2, $consumables->data['total']);
        $this->assertSame(
            [1, 1, 0],
            array_column($consumables->data['breakdown'], 'value'),
        );

        $this->assertSame(SectionState::Ok, $nonConsumables->state);
        $this->assertSame(1, $nonConsumables->data['total']);
        $this->assertSame(
            [0, 0, 1],
            array_column($nonConsumables->data['breakdown'], 'value'),
        );
    }

    private function createEquipment(int $locationId, string $category, string $status, int $suffix): void
    {
        DB::table('equipment')->insert([
            'code' => "E-00{$suffix}",
            'name' => "Equipo de prueba {$suffix}",
            'model' => 'Modelo',
            'serial_number' => "S-00{$suffix}",
            'responsible' => 'Responsable',
            'status' => $status,
            'brand' => 'Marca',
            'plate' => "P-00{$suffix}",
            'location_id' => $locationId,
            'description' => 'Equipo para pruebas',
            'category' => $category,
        ]);
    }
}
