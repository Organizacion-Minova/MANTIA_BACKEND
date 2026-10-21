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
        // Para simular responsables
        $responsibles = ['Ing. Carlos Mendoza', 'Tec. Laura Restrepo', 'Ing. Andrés Beltrán', 'Tec. Diana Silva'];

        $measurements = GasMeasurement::orderBy('id')->get();

        $locations = Location::where('location_category_id', 3)->orderBy('id')->get();

        $dataToInsert = [];

        foreach ($measurements as $measurementIndex => $measurement) {
            $currentResponsible = $responsibles[$measurementIndex] ?? 'Inspector de Turno';
            $timestampReal = "{$measurement->measurement_date} {$measurement->measurement_time}";

            $formulaIndex = 1;

            foreach ($locations as $location) {
                $variance = ($formulaIndex * 0.02);

                // Para simular valores  
                $oxygen          = number_format(20.9 - ($variance * 0.1), 1);
                $methane         = number_format(0.0 + ($formulaIndex == 12 ? 0.2 : 0.0), 2);
                $carbonDioxide   = number_format(0.04 + ($variance * 0.05), 2);
                $hydrogenSulfide = $formulaIndex == 8 ? 1 : 0;
                $carbonMonoxide  = rand(0, 2);
                $nitrogenDioxide = number_format(0.0 + ($variance * 0.01), 2);
                $observation     = 'Lectura normal sin novedades.';

                $exists = GasMeasurementDetail::where('gas_measurement_id', $measurement->id)
                    ->where('location_id', $location->id)
                    ->exists();

                if (!$exists) {
                    $dataToInsert[] = [
                        'gas_measurement_id' => $measurement->id,
                        'location_id'        => $location->id, 
                        'oxygen'             => $oxygen,
                        'methane'            => $methane,
                        'carbon_dioxide'     => $carbonDioxide,
                        'hydrogen_sulfide'   => $hydrogenSulfide,
                        'carbon_monoxide'    => $carbonMonoxide,
                        'nitrogen_dioxide'   => $nitrogenDioxide,
                        'responsible_name'   => $currentResponsible,
                        'observation'        => $observation,                        
                        'created_at'         => $timestampReal, 
                        'updated_at'         => $timestampReal,
                    ];
                }
                $formulaIndex++;
            }
        }

        if (!empty($dataToInsert)) {
            GasMeasurementDetail::insert($dataToInsert);
        }
    }
}
