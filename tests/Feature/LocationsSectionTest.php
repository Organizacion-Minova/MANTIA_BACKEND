<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use App\Services\LocationInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class LocationsSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_inventory_includes_equipment_classification(): void
    {
        $categoryId = DB::table('location_category')->insertGetId([
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
            'category' => $category,
        ]);
    }
}