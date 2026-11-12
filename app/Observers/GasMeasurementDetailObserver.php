<?php

namespace App\Observers;

use App\Models\GasMeasurementDetail;
use App\Services\AlertaService;

/**
 * Revisa los valores de gases de cada ubicación contra los límites de config/alertas.php
 * y avisa al Jefe de mina y al equipo de Seguridad y Salud en el Trabajo (SST).
 *
 * Solo revisa los gases que cambiaron en esta edición, para no repetir la misma
 * alerta cada vez que alguien guarda la observación o el nombre del responsable.
 */
class GasMeasurementDetailObserver
{
    private const GASES = [
        'oxygen'           => ['Oxígeno (O2)', '%'],
        'methane'          => ['Metano (CH4)', '%'],
        'carbon_dioxide'   => ['Dióxido de carbono (CO2)', '%'],
        'hydrogen_sulfide' => ['Sulfuro de hidrógeno (H2S)', ' ppm'],
        'carbon_monoxide'  => ['Monóxido de carbono (CO)', ' ppm'],
        'nitrogen_dioxide' => ['Dióxido de nitrógeno (NO2)', ' ppm'],
    ];

    public function created(GasMeasurementDetail $detalle): void
    {
        $this->revisar($detalle, array_keys(self::GASES));
    }

    public function updated(GasMeasurementDetail $detalle): void
    {
        $cambiados = array_keys(array_intersect_key($detalle->getChanges(), self::GASES));
        $this->revisar($detalle, $cambiados);
    }

    private function revisar(GasMeasurementDetail $detalle, array $campos): void
    {
        $riesgos = [];

        foreach ($campos as $campo) {
            $texto = $this->fueraDeRango($campo, $detalle->{$campo});
            if ($texto) {
                $riesgos[] = $texto;
            }
        }

        if (empty($riesgos)) {
            return; // sin riesgo, sin alerta
        }

        $ubicacion = $detalle->location?->name ?? "ubicación #{$detalle->location_id}";

        $mensajeAlerta = "Riesgo de gases en {$ubicacion}: " . implode('; ', $riesgos)
            . '. Informe al responsable de SST y siga el protocolo de seguridad.';

        foreach (['Jefe de mina', 'SST'] as $rol) {
            AlertaService::porRol(
                $rol,
                'gas',
                $mensajeAlerta,
                'danger',
                "/gases/gas_format/{$detalle->gas_measurement_id}"
            );
        }
    }

    private function fueraDeRango(string $campo, $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = (float) $valor;
        $limite = config("alertas.gas.{$campo}", []);
        [$nombre, $unidad] = self::GASES[$campo];

        if (isset($limite['min']) && $valor < $limite['min']) {
            return "{$nombre} por debajo del límite ({$valor}{$unidad}; mínimo {$limite['min']}{$unidad})";
        }
        if (isset($limite['max']) && $valor > $limite['max']) {
            return "{$nombre} por encima del límite ({$valor}{$unidad}; máximo {$limite['max']}{$unidad})";
        }

        return null;
    }
}