<?php

namespace Tests\Feature;

use App\Dashboard\SectionState;
use App\Dashboard\Sections\ActivitySection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivitySectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_tools_with_bad_or_low_usable_stock(): void
    {
        $locationCategoryId = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
        ]);
        $locationId = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Nivel principal',
            'status' => 'active',
            'location_category_id' => $locationCategoryId,
        ]);

        $groups = config('dashboard.tools.groups');
        $consumableGroupId = DB::table('category_group')->insertGetId([
            'name' => $groups['consumables'],
        ]);
        $nonConsumableGroupId = DB::table('category_group')->insertGetId([
            'name' => $groups['non_consumables'],
        ]);
        $consumableCategoryId = $this->createCategory($consumableGroupId, 'Activo');
        $nonConsumableCategoryId = $this->createCategory($nonConsumableGroupId, 'Activo');
        $inactiveCategoryId = $this->createCategory($consumableGroupId, 'Inactivo');

        $lowStockId = $this->createTool($consumableCategoryId, $locationId, 'Taladro bajo', [
            'bueno' => 2,
            'regular' => 1,
        ]);
        $badConditionId = $this->createTool($nonConsumableCategoryId, $locationId, 'Sierra mala', [
            'bueno' => 5,
            'malo' => 2,
        ]);
        $this->createTool($consumableCategoryId, $locationId, 'Llave disponible', [
            'bueno' => 5,
        ]);
        $this->createTool($inactiveCategoryId, $locationId, 'Herramienta de categoría inactiva', [
            'malo' => 10,
        ]);
        $emptyStockId = $this->createTool($consumableCategoryId, $locationId, 'Llave sin stock');

        $result = (new ActivitySection())->resolve();

        $this->assertSame(SectionState::Ok, $result->state);
        $this->assertSame(
            ["tool-{$badConditionId}", "tool-{$emptyStockId}", "tool-{$lowStockId}"],
            array_column($result->data, 'id'),
        );
        $this->assertSame(2, $result->data[0]['badStock']);
        $this->assertSame(5, $result->data[0]['usableStock']);
        $this->assertFalse($result->data[0]['lowStock']);
        $this->assertSame('/noconsumables/tools', $result->data[0]['href']);
        $this->assertSame(0, $result->data[1]['usableStock']);
        $this->assertTrue($result->data[1]['lowStock']);
        $this->assertTrue($result->data[2]['lowStock']);
    }

    public function test_returns_empty_when_no_asset_needs_attention(): void
    {
        $locationCategoryId = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
        ]);
        $locationId = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Nivel principal',
            'status' => 'active',
            'location_category_id' => $locationCategoryId,
        ]);
        $groupId = DB::table('category_group')->insertGetId([
            'name' => config('dashboard.tools.groups.consumables'),
        ]);
        $categoryId = $this->createCategory($groupId, 'Activo');
        $this->createTool($categoryId, $locationId, 'Herramienta utilizable', [
            'bueno' => 5,
        ]);

        $result = (new ActivitySection())->resolve();

        $this->assertSame(SectionState::Empty, $result->state);
        $this->assertSame([], $result->data);
    }

    public function test_returns_all_attention_items_when_there_are_more_than_six(): void
    {
        $locationCategoryId = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
        ]);
        $locationId = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Nivel principal',
            'status' => 'active',
            'location_category_id' => $locationCategoryId,
        ]);
        $groupId = DB::table('category_group')->insertGetId([
            'name' => config('dashboard.tools.groups.consumables'),
        ]);
        $categoryId = $this->createCategory($groupId, 'Activo');

        for ($index = 1; $index <= 7; $index++) {
            $this->createTool($categoryId, $locationId, "Herramienta {$index}");
        }

        $result = (new ActivitySection())->resolve();

        $this->assertSame(SectionState::Ok, $result->state);
        $this->assertCount(7, $result->data);
    }

    public function test_returns_machines_and_equipment_out_of_service_or_in_maintenance(): void
    {
        $locationCategoryId = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
        ]);
        $locationId = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Nivel principal',
            'status' => 'active',
            'location_category_id' => $locationCategoryId,
        ]);
        $machineCategoryId = DB::table('machine_category')->insertGetId([
            'name' => 'Maquinaria',
            'description' => 'Maquinaria de pruebas',
            'status' => 'active',
        ]);

        $machineId = DB::table('machine')->insertGetId([
            'code' => 'M-001',
            'name' => 'Bomba en mantenimiento',
            'status' => 'maintenance',
            'location_id' => $locationId,
            'machine_category_id' => $machineCategoryId,
            'brand' => 'Marca',
            'responsible' => 'Responsable',
            'machine_usage' => 'Bombeo',
            'characteristics' => 'Bomba de pruebas',
        ]);
        DB::table('machine')->insert([
            'code' => 'M-002',
            'name' => 'Bomba activa',
            'status' => 'active',
            'location_id' => $locationId,
            'machine_category_id' => $machineCategoryId,
            'brand' => 'Marca',
            'responsible' => 'Responsable',
            'machine_usage' => 'Bombeo',
            'characteristics' => 'Bomba de pruebas',
        ]);
        $equipmentId = DB::table('equipment')->insertGetId([
            'code' => 'E-001',
            'name' => 'Taladro fuera de servicio',
            'model' => 'Modelo',
            'serial_number' => 'S-001',
            'responsible' => 'Responsable',
            'status' => 'inactive',
            'brand' => 'Marca',
            'location_id' => $locationId,
            'description' => 'Equipo de pruebas',
        ]);

        $result = (new ActivitySection())->resolve();

        $this->assertSame(SectionState::Ok, $result->state);
        $this->assertSame(
            ["equipment-{$equipmentId}", "machine-{$machineId}"],
            array_column($result->data, 'id'),
        );
        $this->assertSame('/equipment/cardequipment/'.$equipmentId, $result->data[0]['href']);
        $this->assertSame('maintenance', $result->data[1]['status']);
    }

    private function createCategory(int $groupId, string $status): int
    {
        static $counter = 0;

        return DB::table('category')->insertGetId([
            'name' => 'Categoría '.++$counter,
            'description' => 'Categoría de pruebas',
            'status' => $status,
            'category_group_id' => $groupId,
        ]);
    }

    private function createTool(int $categoryId, int $locationId, string $name, array $stock = []): int
    {
        $toolId = DB::table('tool')->insertGetId([
            'name' => $name,
            'location_id' => $locationId,
            'category_id' => $categoryId,
        ]);

        foreach ($stock as $condition => $units) {
            DB::table('tool_stock_condition')->insert([
                'tool_id' => $toolId,
                'condition' => $condition,
                'stock' => $units,
            ]);
        }

        return $toolId;
    }
}
