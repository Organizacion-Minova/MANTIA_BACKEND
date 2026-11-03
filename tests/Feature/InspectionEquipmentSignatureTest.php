<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InspectionEquipmentSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_replaces_signature_images(): void
    {
        $this->withoutMiddleware();
        Storage::fake('public');
        $formatId = $this->createFormatFixture();
        $data = [
            'format_id' => $formatId,
            'week_start' => '2026-11-02',
            'signatures' => [
                ['role' => 'inspector', 'name' => 'Ana'],
            ],
        ];

        $response = $this->post('/api/inspections', [
            'data' => json_encode($data),
            'signature_images' => [
                'inspector' => UploadedFile::fake()->image('signature.png'),
            ],
        ])->assertCreated();

        $firstPath = $response->json('signatures.0.signature');
        $this->assertNotEmpty($firstPath);
        Storage::disk('public')->assertExists($firstPath);

        $this->postJson('/api/inspections', $data)->assertOk();
        $this->assertDatabaseHas('inspection_signature_equipment', [
            'role' => 'inspector',
            'signature' => $firstPath,
        ]);
        Storage::disk('public')->assertExists($firstPath);

        $response = $this->post('/api/inspections', [
            'data' => json_encode($data),
            'signature_images' => [
                'inspector' => UploadedFile::fake()->image('replacement.png'),
            ],
        ])->assertOk();

        $replacementPath = $response->json('signatures.0.signature');
        $this->assertNotSame($firstPath, $replacementPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($replacementPath);
    }

    private function createFormatFixture(): int
    {
        $locationCategoryId = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
            'status' => 'active',
        ]);
        $locationId = DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Nivel principal',
            'status' => 'active',
            'location_category_id' => $locationCategoryId,
        ]);
        $equipmentId = DB::table('equipment')->insertGetId([
            'code' => 'EQ-SIGN-001',
            'name' => 'Equipo de prueba',
            'model' => 'Modelo 1',
            'serial_number' => 'SN-SIGN-001',
            'responsible' => 'Ana',
            'status' => 'active',
            'brand' => 'Marca',
            'location_id' => $locationId,
            'description' => 'Equipo para pruebas',
        ]);

        return DB::table('inspection_format_equipment')->insertGetId([
            'equipment_id' => $equipmentId,
            'code' => 'FORM-SIGN-01',
            'version' => '01',
            'is_active' => true,
        ]);
    }
}
