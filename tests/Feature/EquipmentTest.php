<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_image_can_be_added_replaced_and_removed_while_editing(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');
        $references = $this->createEquipmentReferences();

        $equipmentData = [
            'code' => 'EQ-001',
            'name' => 'Detector de gases',
            'description' => 'Detector portátil',
            'serial_number' => 'SN-001',
            'responsible' => 'Ana',
            'status' => 'active',
            'registration_date' => '2025-01-15',
            'company_id' => $references['company'],
            'location_id' => $references['location'],
            'model' => 'D-1',
        ];

        $equipmentId = $this->postJson('/api/equipment', $equipmentData)
            ->assertCreated()
            ->assertJsonPath('image', null)
            ->json('id');

        $firstImage = UploadedFile::fake()->image('equipment.png');
        $created = $this->patch("/api/equipment/{$equipmentId}", [
            'name' => 'Detector portátil',
            'image' => $firstImage,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('name', 'Detector portátil');

        $firstImagePath = $created->json('image');
        $this->assertNotEmpty($firstImagePath);
        Storage::disk('public')->assertExists($firstImagePath);

        $this->patchJson("/api/equipment/{$equipmentId}", ['description' => 'Detector actualizado'])
            ->assertOk()
            ->assertJsonPath('description', 'Detector actualizado')
            ->assertJsonPath('image', $firstImagePath);
        Storage::disk('public')->assertExists($firstImagePath);

        $replacement = UploadedFile::fake()->image('replacement.png');
        $updated = $this->patch("/api/equipment/{$equipmentId}", [
            'image' => $replacement,
        ], ['Accept' => 'application/json'])->assertOk();
        $replacementPath = $updated->json('image');
        $this->assertNotEmpty($replacementPath);
        Storage::disk('public')->assertExists($replacementPath);
        Storage::disk('public')->assertMissing($firstImagePath);

        $this->deleteJson("/api/equipment/{$equipmentId}")->assertOk();
        Storage::disk('public')->assertMissing($replacementPath);
    }

    /**
     * @return array{company: int, location: int}
     */
    private function createEquipmentReferences(): array
    {
        $company = DB::table('company')->insertGetId([
            'name' => 'Minova',
            'phone' => '123456',
            'email' => 'test@example.com',
            'address' => 'Mina',
            'nit' => uniqid('nit'),
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

        return compact('company', 'location');
    }
}
