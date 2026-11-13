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
        $startDate = now()->subDays(15);

        for ($i = 0; $i < 14; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $time = ($i % 2 === 0) ? '07:00:00' : '14:30:00';

            GasMeasurement::firstOrCreate(
                ['measurement_date' => $date],
                [
                    'measurement_time' => $time,
                    'chief_signature'  => 'Firma digital - Jefe de Mina',
                    'sst_signature'    => 'Firma digital - Líder SST',
                    'created_at'       => "{$date} {$time}",
                    'updated_at'       => "{$date} {$time}",
                ]
            );
        }
    }
}