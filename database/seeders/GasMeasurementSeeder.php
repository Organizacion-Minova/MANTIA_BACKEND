<?php

namespace Database\Seeders;

use App\Models\GasMeasurement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class GasMeasurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GasMeasurement::firstOrCreate(
            ['measurement_date' => '2026-10-20'],
            [
                'measurement_time' => '07:15:00',
                'chief_signature' => null,
                'sst_signature' => null,
                'created_at' => '2026-10-20 07:15:00',
                'updated_at' => '2026-10-20 07:15:00'
            ]
        );

        GasMeasurement::firstOrCreate(
            ['measurement_date' => '2026-10-19'],
            [
                'measurement_time' => '08:00:00',
                'chief_signature' => null,
                'sst_signature' => null,
                'created_at' => '2026-10-19 08:00:00', 
                'updated_at' => '2026-10-19 08:00:00'
            ]
        );
    }
}
