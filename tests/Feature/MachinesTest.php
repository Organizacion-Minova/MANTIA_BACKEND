<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MachinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_machine_crud_uses_current_model_fields(): void
    {
        $this->withoutMiddleware();
        $references = $this->createMachineReferences();

        $machineData = [
            'code' => 'M-001',
            'name' => 'Bomba principal',
            'model' => str_repeat('M', 70),
            'serial_number' => str_repeat('S', 90),
            'responsible' => 'Operador',
            'status' => 'active',
            'start_up_date' => '2025-01-15',
            'brand' => 'Minova',
            'plate' => 'PL-001',
            'fuel' => 'Diesel',
            'power' => '100 W',
            'voltage' => '220 V',
            'location_id' => $references['location'],
            'machine_category_id' => $references['category'],
            'machine_usage' => 'Desague',
            'characteristics' => str_repeat('Caracteristica ', 60),
        ];

        $created = $this->postJson('/api/machines', $machineData)
            ->assertCreated()
            ->assertJsonPath('serial_number', str_repeat('S', 90))
            ->assertJsonPath('start_up_date', '2025-01-15')
            ->assertJsonPath('plate', 'PL-001');
        $machineId = $created->json('id');

        $this->getJson('/api/machines')->assertOk()->assertJsonCount(1);
        $this->getJson("/api/machines/{$machineId}")
            ->assertOk()
            ->assertJsonPath('fuel', 'Diesel');

        $machineData['name'] = 'Bomba de reserva';
        $machineData['plate'] = 'PL-002';
        $this->putJson("/api/machines/{$machineId}", $machineData)
            ->assertOk()
            ->assertJsonPath('name', 'Bomba de reserva')
            ->assertJsonPath('plate', 'PL-002');

        $this->deleteJson("/api/machines/{$machineId}")->assertOk();
        $this->assertDatabaseMissing('machine', ['id' => $machineId]);
        $this->getJson('/api/machines')->assertOk()->assertJsonCount(0);
    }

    public function test_machine_optional_model_fields_accept_null_values(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');
        $references = $this->createMachineReferences();

        $machineData = [
            'code' => 'M-002',
            'name' => 'Compresor',
            'model' => null,
            'serial_number' => null,
            'responsible' => 'Operador',
            'status' => 'active',
            'start_up_date' => null,
            'brand' => 'Minova',
            'plate' => null,
            'fuel' => null,
            'power' => null,
            'voltage' => null,
            'location_id' => $references['location'],
            'machine_category_id' => $references['category'],
            'machine_usage' => 'Aire',
            'characteristics' => 'Compresor industrial',
        ];

        $created = $this->postJson('/api/machines', $machineData)
            ->assertCreated()
            ->assertJsonPath('model', null)
            ->assertJsonPath('serial_number', null)
            ->assertJsonPath('start_up_date', null)
            ->assertJsonPath('plate', null)
            ->assertJsonPath('image', null);
        $machineId = $created->json('id');

        $this->putJson("/api/machines/{$machineId}", $machineData)
            ->assertOk()
            ->assertJsonPath('fuel', null)
            ->assertJsonPath('voltage', null);

        $this->deleteJson("/api/machines/{$machineId}")->assertOk();
    }

    public function test_machine_image_is_optional_replaced_and_deleted(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');
        $references = $this->createMachineReferences();
        $machineData = [
            'code' => 'M-003',
            'name' => 'Generador',
            'model' => 'G-1',
            'serial_number' => 'SN-003',
            'responsible' => 'Operador',
            'status' => 'active',
            'start_up_date' => null,
            'brand' => 'Minova',
            'plate' => null,
            'fuel' => 'Diesel',
            'power' => null,
            'voltage' => null,
            'location_id' => $references['location'],
            'machine_category_id' => $references['category'],
            'machine_usage' => 'Energía',
            'characteristics' => 'Generador industrial',
        ];

        $machineId = $this->postJson('/api/machines', $machineData)
            ->assertCreated()
            ->assertJsonPath('image', null)
            ->json('id');

        $firstUpdate = $this->put("/api/machines/{$machineId}", [
            ...$machineData,
            'image' => UploadedFile::fake()->image('machine.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        $firstImagePath = $firstUpdate->json('image');
        $this->assertNotEmpty($firstImagePath);
        Storage::disk('public')->assertExists($firstImagePath);

        $secondUpdate = $this->put("/api/machines/{$machineId}", [
            ...$machineData,
            'image' => UploadedFile::fake()->image('replacement.png'),
        ], ['Accept' => 'application/json'])->assertOk();
        $secondImagePath = $secondUpdate->json('image');
        Storage::disk('public')->assertMissing($firstImagePath);
        Storage::disk('public')->assertExists($secondImagePath);

        $this->deleteJson("/api/machines/{$machineId}")->assertOk();
        Storage::disk('public')->assertMissing($secondImagePath);
    }

    private function createMachineReferences(): array
    {
        Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);

        $category = DB::table('machine_category')->insertGetId([
            'name' => 'Bombas',
            'description' => 'Equipos de bombeo',
            'status' => 'active',
        ]);
        $locationCategory = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
        ]);
        $location = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Nivel principal',
            'status' => 'active',
            'location_category_id' => $locationCategory,
        ]);

        return compact('category', 'location');
    }
}
