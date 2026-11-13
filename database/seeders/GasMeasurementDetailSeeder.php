<?php

namespace Database\Seeders;

use App\Models\GasMeasurement;
use App\Models\Location;
use App\Models\GasMeasurementDetail;
use Illuminate\Database\Seeder;

class GasMeasurementDetailSeeder extends Seeder
{
    public function run(): void
    {
        $measurements = GasMeasurement::orderBy('id')->get();
        $locations = Location::where('location_category_id', 3)->orderBy('id')->get();

        $dataToInsert = [];

        foreach ($measurements as $measurementIndex => $measurement) {
            $timestampReal = "{$measurement->measurement_date} {$measurement->measurement_time}";
            $locationIndex = 0;

            foreach ($locations as $location) {
                $isHighMethane = ($measurementIndex === 3 && $locationIndex === 1); 
                $isLowOxygen = ($measurementIndex === 7 && $locationIndex === 2); 
                $isToxicSpike = ($measurementIndex === 11 && $locationIndex === 0); 

                if ($isHighMethane) {
                    $oxygen = 20.4;
                    $methane = 1.35; 
                    $carbonDioxide = 0.35;
                    $hydrogenSulfide = 0;
                    $carbonMonoxide = 4;
                    $nitrogenDioxide = 0.05;
                    $observation = 'Se requiere ventilación forzada.';
                } elseif ($isLowOxygen) {
                    $oxygen = 18.9; 
                    $methane = 0.10;
                    $carbonDioxide = 0.62;
                    $hydrogenSulfide = 1;
                    $carbonMonoxide = 8;
                    $nitrogenDioxide = 0.08;
                    $observation = 'Deficiencia de Oxígeno.';
                } elseif ($isToxicSpike) {
                    $oxygen = 20.2;
                    $methane = 0.20;
                    $carbonDioxide = 0.30;
                    $hydrogenSulfide = 2;
                    $carbonMonoxide = 34;
                    $nitrogenDioxide = 0.28;
                    $observation = 'Presencia de gases sobre los VLP.';
                } else {
                    $oxygen = number_format(rand(205, 218) / 10, 1);
                    $methane = number_format(rand(0, 4) / 100, 2);
                    $carbonDioxide = number_format(rand(5, 30) / 100, 2);
                    $hydrogenSulfide = 0;
                    $carbonMonoxide = rand(0, 5);
                    $nitrogenDioxide = number_format(rand(1, 10) / 100, 2);
                    $observation = 'Lectura normal sin novedades.';
                }

                $exists = GasMeasurementDetail::where('gas_measurement_id', $measurement->id)
                    ->where('location_id', $location->id)
                    ->exists();

                if (!$exists) {
                    $dataToInsert[] = [
                        'gas_measurement_id' => $measurement->id,
                        'location_id' => $location->id, 
                        'oxygen' => $oxygen,
                        'methane' => $methane,
                        'carbon_dioxide' => $carbonDioxide,
                        'hydrogen_sulfide' => $hydrogenSulfide,
                        'carbon_monoxide' => $carbonMonoxide,
                        'nitrogen_dioxide' => $nitrogenDioxide,
                        'responsible_name' => 'Operario gases',
                        'observation' => $observation,                        
                        'created_at' => $timestampReal, 
                        'updated_at' => $timestampReal,
                    ];
                }
                $locationIndex++;
            }
        }

        if (!empty($dataToInsert)) {
            GasMeasurementDetail::insert($dataToInsert);
        }
    }
}
