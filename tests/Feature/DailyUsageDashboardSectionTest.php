<?php

namespace Tests\Feature;

use App\Dashboard\Sections\DailyUsageSection;
use App\Dashboard\SectionState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DailyUsageDashboardSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_first_and_last_daily_use_per_machine_including_machines_without_records(): void
    {
        $locationId = $this->createLocation();
        $machineCategoryId = DB::table('machine_category')->insertGetId([
            'name' => 'Maquinaria',
            'description' => 'Categoría de pruebas',
            'status' => 'active',
        ]);
        $machineWithUsageId = $this->createMachine($locationId, $machineCategoryId, 'Máquina con uso');
        $machineWithoutUsageId = $this->createMachine($locationId, $machineCategoryId, 'Máquina sin uso');

        DB::table('daily_use')->insert([
            'date' => '2026-10-01',
            'operating_status' => 'Operativa',
            'operation_start' => '07:00:00',
            'operation_end' => '15:00:00',
            'machine_id' => $machineWithUsageId,
        ]);
        DB::table('daily_use')->insert([
            'date' => '2026-10-10',
            'operating_status' => 'Mantenimiento',
            'operation_start' => '08:00:00',
            'operation_end' => '12:00:00',
            'machine_id' => $machineWithUsageId,
        ]);

        $result = (new DailyUsageSection)->resolve();

        $this->assertSame(SectionState::Ok, $result->state);
        $this->assertCount(2, $result->data);
        $machineWithUsage = collect($result->data)->firstWhere('id', $machineWithUsageId);
        $machineWithoutUsage = collect($result->data)->firstWhere('id', $machineWithoutUsageId);

        $this->assertSame(2, $machineWithUsage['recordCount']);
        $this->assertSame(['date' => '2026-10-01'], $machineWithUsage['firstRecord']);
        $this->assertSame('2026-10-10', $machineWithUsage['lastRecord']['date']);
        $this->assertSame(0, $machineWithoutUsage['recordCount']);
        $this->assertNull($machineWithoutUsage['firstRecord']);
        $this->assertNull($machineWithoutUsage['lastRecord']);
    }

    public function test_returns_empty_when_no_machines_exist(): void
    {
        $result = (new DailyUsageSection)->resolve();

        $this->assertSame(SectionState::Empty, $result->state);
        $this->assertSame([], $result->data);
    }

    private function createLocation(): int
    {
        $categoryId = DB::table('location_category')->insertGetId([
            'name' => 'Interior',
            'description' => 'Ubicaciones interiores',
        ]);

        return DB::table('location')->insertGetId([
            'name' => 'Nivel 1',
            'description' => 'Ubicación de pruebas',
            'status' => 'active',
            'location_category_id' => $categoryId,
        ]);
    }

    private function createMachine(int $locationId, int $machineCategoryId, string $name): int
    {
        static $suffix = 0;
        $suffix++;

        return DB::table('machine')->insertGetId([
            'code' => "M-{$suffix}",
            'name' => $name,
            'responsible' => 'Responsable',
            'status' => 'Activo',
            'brand' => 'Marca',
            'plate' => "P-{$suffix}",
            'location_id' => $locationId,
            'machine_category_id' => $machineCategoryId,
        ]);
    }
}
