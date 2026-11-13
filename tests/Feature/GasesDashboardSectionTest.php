<?php

namespace Tests\Feature;

use App\Dashboard\Sections\GasesSection;
use App\Dashboard\SectionState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GasesDashboardSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_latest_measurement_by_date_for_each_location(): void
    {
        $categoryId = DB::table('location_category')->insertGetId([
            'name' => 'Mina',
            'description' => 'Ubicaciones de la mina',
        ]);
        $firstLocationId = $this->createLocation($categoryId, 'Nivel 1');
        $secondLocationId = $this->createLocation($categoryId, 'Nivel 2');

        $latestMeasurementId = $this->createMeasurement('2026-10-12', '10:00:00');
        $olderMeasurementId = $this->createMeasurement('2026-10-10', '12:00:00');
        $measurementWithoutTimeId = $this->createMeasurement('2026-10-12', null);
        $laterMeasurementId = $this->createMeasurement('2026-10-12', '11:00:00');

        $this->createDetail($firstLocationId, $latestMeasurementId, 20.5);
        $this->createDetail($firstLocationId, $olderMeasurementId, 18.0);
        $this->createDetail($firstLocationId, $laterMeasurementId, 21.0);
        $this->createDetail($secondLocationId, $olderMeasurementId, 20.0, 1.25);
        $this->createDetail($secondLocationId, $measurementWithoutTimeId, 20.0, 2.0);
        $this->createDetail($secondLocationId, $laterMeasurementId, 20.0, 1.5);

        $result = (new GasesSection)->resolve();

        $this->assertSame(SectionState::Ok, $result->state);
        $this->assertCount(2, $result->data);
        $this->assertSame($laterMeasurementId, $result->data[0]['measurement']['id']);
        $this->assertSame($firstLocationId, $result->data[0]['id']);
        $this->assertEquals(21.0, (float) $result->data[0]['detail']['oxygen']);
        $this->assertSame($laterMeasurementId, $result->data[1]['measurement']['id']);
        $this->assertSame($secondLocationId, $result->data[1]['id']);
        $this->assertEquals(1.5, (float) $result->data[1]['detail']['methane']);
    }

    public function test_returns_empty_when_no_gas_measurements_exist(): void
    {
        $result = (new GasesSection)->resolve();

        $this->assertSame(SectionState::Empty, $result->state);
        $this->assertSame([], $result->data);
    }

    public function test_orders_locations_in_the_gas_format_order(): void
    {
        $categoryId = DB::table('location_category')->insertGetId([
            'name' => 'Mina',
            'description' => 'Ubicaciones de la mina',
        ]);
        $expectedOrder = [
            'Tunel 1',
            'Tunel 2',
            'Tunel 3',
            'Cruzada de galeria 1',
            'Galeria 2 centro',
            'Galeria 2 norte',
            'Galeria 2 sur',
            'Transversal norte',
            'Transversal sur',
            'Galeria 3 centro',
            'Galeria 3 sur',
            'Galeria 3 norte',
            'Cruzada de galeria 33',
            'Diagonal norte',
            'Diagonal sur',
            'Galeria 4',
        ];
        $measurementId = $this->createMeasurement('2026-10-12', '10:00:00');

        foreach (array_reverse($expectedOrder) as $name) {
            $locationId = $this->createLocation($categoryId, $name);
            $this->createDetail($locationId, $measurementId, 20.5);
        }

        $result = (new GasesSection)->resolve();

        $this->assertSame($expectedOrder, array_column($result->data, 'name'));
    }

    private function createLocation(int $categoryId, string $name): int
    {
        return DB::table('location')->insertGetId([
            'name' => $name,
            'description' => 'Ubicación de pruebas',
            'status' => 'active',
            'location_category_id' => $categoryId,
        ]);
    }

    private function createMeasurement(string $date, ?string $time): int
    {
        return DB::table('gas_measurement')->insertGetId([
            'measurement_date' => $date,
            'measurement_time' => $time,
        ]);
    }

    private function createDetail(int $locationId, int $measurementId, float $oxygen, float $methane = 0.1): void
    {
        DB::table('gas_measurement_detail')->insert([
            'gas_measurement_id' => $measurementId,
            'location_id' => $locationId,
            'oxygen' => $oxygen,
            'methane' => $methane,
            'carbon_dioxide' => 0.1,
            'hydrogen_sulfide' => 0.1,
            'carbon_monoxide' => 0.1,
            'nitrogen_dioxide' => 0.1,
        ]);
    }
}
