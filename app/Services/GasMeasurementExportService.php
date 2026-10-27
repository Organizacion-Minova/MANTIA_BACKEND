<?php

namespace App\Services;

use App\Models\GasMeasurement;
use Carbon\Carbon;

class GasMeasurementExportService
{
    public function obtenerDatosUnitarios($id)
    {
        return GasMeasurement::with([
            'detail' => fn ($query) => $query->orderBy('id'),
            'detail.location',
        ])->findOrFail($id);
    }

    public function obtenerDatosRango($fechaInicio, $fechaFin)
    {
        return GasMeasurement::with([
            'detail' => fn ($query) => $query->orderBy('id'),
            'detail.location',
        ])
        ->whereBetween('measurement_date', [$fechaInicio, $fechaFin])
        ->orderBy('measurement_date', 'asc')
        ->get();
    }
}
