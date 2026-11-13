<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Services\LocationInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use App\Dashboard\Sections\LocationsSection;
use App\Models\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocationsSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_inventory_includes_equipment_classification(): void
    {
        $categoryId = DB::table('location_category')->insertGetId([
    public function test_breaks_down_equipment_by_consumable_category(): void
    {
        $locationCategoryId = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
        ]);
        $locationId = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Nivel principal',
            'status' => 'active',
            'location_category_id' => $categoryId,
        ]);

        $this->insertEquipment($locationId, 'EQ-C', 'Equipo consumible', 'Consumible');
        $this->insertEquipment($locationId, 'EQ-NC', 'Equipo no consumible', 'NoConsumible');

        $usuario = Mockery::mock(User::class);
        $usuario->shouldReceive('can')
            ->andReturnUsing(fn ($permiso) => $permiso === 'equipment.view');

        $inventario = app(LocationInventoryService::class)->obtener(
            $usuario,
            Location::findOrFail($locationId)
        );

        $this->assertSame(
            ['Consumible', 'NoConsumible'],
            array_column($inventario['equipos'], 'categoria')
        );
    }

    private function insertEquipment(int $locationId, string $code, string $name, string $category): void
    {
        DB::table('equipment')->insert([
            'code' => $code,
            'name' => $name,
            'model' => 'Modelo',
            'serial_number' => "SN-{$code}",
            'responsible' => 'Ana',
            'brand' => 'Minova',
            'plate' => $code,
            'location_id' => $locationId,
            'description' => 'Ubicación de pruebas',
            'status' => 'active',
            'location_category_id' => $locationCategoryId,
        ]);

        $this->createEquipment($locationId, Equipment::CATEGORY_CONSUMABLE, 1);
        $this->createEquipment($locationId, Equipment::CATEGORY_NON_CONSUMABLE, 2);

        $locations = (new LocationsSection())->resolve()->data;

        $this->assertSame(2, $locations[0]['total']);
        $this->assertSame([
            ['key' => 'machines', 'label' => 'Máquinas', 'quantity' => 0],
            ['key' => 'equipment_consumables', 'label' => 'Equipos consumibles', 'quantity' => 1],
            ['key' => 'equipment_non_consumables', 'label' => 'Equipos no consumibles', 'quantity' => 1],
            ['key' => 'tools', 'label' => 'Herramientas', 'quantity' => 0],
        ], $locations[0]['items']);
    }

    private function createEquipment(int $locationId, string $category, int $suffix): void
    {
        DB::table('equipment')->insert([
            'code' => "E-00{$suffix}",
            'name' => "Equipo de prueba {$suffix}",
            'model' => 'Modelo',
            'serial_number' => "S-00{$suffix}",
            'responsible' => 'Responsable',
            'status' => 'Activo',
            'brand' => 'Marca',
            'plate' => "P-00{$suffix}",
            'location_id' => $locationId,
            'description' => 'Equipo para pruebas',
            'category' => $category,
        ]);
    }
}