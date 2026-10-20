<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MachinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_machine_crud_works_without_an_image(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');
        $references = $this->createMachineReferences();

        $machineData = [
            'code' => 'M-001',
            'name' => 'Bomba principal',
            'model' => str_repeat('M', 70),
            'serial_number' => str_repeat('S', 90),
            'acquisition_date' => '2025-01-15',
            'acquisition_cost' => 15000,
            'status' => 'active',
            'warranty' => 12,
            'location_id' => $references['location'],
            'machine_category_id' => $references['category'],
            'company_id' => $references['company'],
            'responsible' => str_repeat('R', 45),
            'machine_usage' => 'Desague',
            'in_operation' => 'false',
            'characteristics' => str_repeat('Caracteristica ', 60),
        ];

        $created = $this->postJson('/api/machines', $machineData)
            ->assertCreated()
            ->assertJsonPath('image', null)
            ->assertJsonPath('in_operation', false);
        $machineId = $created->json('id');

        $this->getJson('/api/machines')->assertOk()->assertJsonCount(1);
        $this->getJson("/api/machines/{$machineId}")
            ->assertOk()
            ->assertJsonPath('warranty', 12);

        $machineData['name'] = 'Bomba de reserva';
        $machineData['in_operation'] = 'true';
        $this->putJson("/api/machines/{$machineId}", $machineData)
            ->assertOk()
            ->assertJsonPath('name', 'Bomba de reserva')
            ->assertJsonPath('in_operation', true);

        $this->deleteJson("/api/machines/{$machineId}")->assertOk();
        $this->assertDatabaseMissing('machine', ['id' => $machineId]);
        $this->getJson('/api/machines')->assertOk()->assertJsonCount(0);
    }

    public function test_machine_image_is_optional_on_create_and_update(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');
        $references = $this->createMachineReferences();

        $machineData = [
            'code' => 'M-002',
            'name' => 'Compresor',
            'model' => 'C-1',
            'serial_number' => 'SN-002',
            'acquisition_date' => '2025-01-15',
            'acquisition_cost' => 1000,
            'status' => 'active',
            'warranty' => null,
            'location_id' => $references['location'],
            'machine_category_id' => $references['category'],
            'company_id' => $references['company'],
            'responsible' => 'Encargado',
            'machine_usage' => 'Aire',
            'in_operation' => true,
            'characteristics' => 'Compresor industrial',
        ];

        $machineId = $this->postJson('/api/machines', $machineData)
            ->assertCreated()
            ->json('id');

        $machineData['image'] = UploadedFile::fake()->createWithContent(
            'machine.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC')
        );
        $updated = $this->put("/api/machines/{$machineId}", $machineData, [
            'Accept' => 'application/json',
        ])->assertOk();
        $imagePath = $updated->json('image');
        $this->assertNotEmpty($imagePath);
        Storage::disk('public')->assertExists($imagePath);

        $this->deleteJson("/api/machines/{$machineId}")->assertOk();
        Storage::disk('public')->assertMissing($imagePath);
    }

    /**
     * @return array{company: int, category: int, location: int}
     */
    private function createMachineReferences(): array
    {
        $company = DB::table('company')->insertGetId([
            'name' => 'Minova',
            'phone' => '123456',
            'email' => 'test@example.com',
            'address' => 'Mina',
            'nit' => uniqid('nit'),
        ]);
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

        return compact('company', 'category', 'location');
    }
}
