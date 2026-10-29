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

    public function test_equipment_image_is_saved_when_created_replaced_and_removed(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');
        $locationId = $this->createEquipmentLocation();

        $equipmentData = [
            'code' => 'EQ-001',
            'name' => 'Detector de gases',
            'description' => 'Detector portátil',
            'serial_number' => 'SN-001',
            'responsible' => 'Ana',
            'status' => 'active',
            'model' => 'D-1',
            'start_up_date' => '2025-01-15',
            'brand' => 'Minova',
            'plate' => 'PL-001',
            'fuel' => 'Eléctrico',
            'power' => '100 W',
            'voltage' => '220 V',
            'location_id' => $locationId,
        ];

        $created = $this->post('/api/equipment', [
            ...$equipmentData,
            'image' => UploadedFile::fake()->image('equipment.png'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('name', 'Detector de gases');

        $equipmentId = $created->json('id');
        $firstImagePath = $created->json('image');
        $this->assertNotEmpty($firstImagePath);
        Storage::disk('public')->assertExists($firstImagePath);

        $replacement = UploadedFile::fake()->image('replacement.png');
        $updated = $this->put("/api/equipment/{$equipmentId}", [
            ...$equipmentData,
            'name' => 'Detector portátil',
            'image' => $replacement,
        ], ['Accept' => 'application/json'])->assertOk();
        $updated->assertJsonPath('name', 'Detector portátil');

        $replacementPath = $updated->json('image');
        $this->assertNotEmpty($replacementPath);
        Storage::disk('public')->assertExists($replacementPath);
        Storage::disk('public')->assertMissing($firstImagePath);

        $this->put("/api/equipment/{$equipmentId}", [
            ...$equipmentData,
            'name' => 'Detector portátil',
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('image', $replacementPath);
        Storage::disk('public')->assertExists($replacementPath);

        $this->deleteJson("/api/equipment/{$equipmentId}")->assertOk();
        Storage::disk('public')->assertMissing($replacementPath);
    }

    private function createEquipmentLocation(): int
    {
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

        return $location;
    }
}
