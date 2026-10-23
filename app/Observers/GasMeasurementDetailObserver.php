<?php

namespace App\Observers;

use App\Models\GasMeasurementDetail;
use App\Services\AlertaService;

/**
 * Revisa los valores de gases de cada ubicación contra los límites de config/alertas.php
 * y avisa al Administrador si alguno está fuera de rango.
 *
 * Solo revisa los gases que cambiaron en esta edición, para no repetir la misma
 * alerta cada vez que alguien guarda la observación o el nombre del responsable.
 */
class GasMeasurementDetailObserver
{
    private const GASES = [
        'oxygen'           => ['Oxígeno', '%'],
        'methane'          => ['Metano', '%'],
        'carbon_dioxide'   => ['Dióxido de carbono', '%'],
        'hydrogen_sulfide' => ['Ácido sulfhídrico', ' ppm'],
        'carbon_monoxide'  => ['Monóxido de carbono', ' ppm'],
        'nitrogen_dioxide' => ['Dióxido de nitrógeno', ' ppm'],
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

        AlertaService::porRol(
            'Administrador',
            'gas',
            "Riesgo detectado en {$ubicacion}: " . implode('; ', $riesgos) . '. Avisar a SST.',
            'danger'
        );
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
            return "{$nombre} bajo ({$valor}{$unidad}, mínimo {$limite['min']}{$unidad})";
        }
        if (isset($limite['max']) && $valor > $limite['max']) {
            return "{$nombre} elevado ({$valor}{$unidad}, máximo {$limite['max']}{$unidad})";
        }

        return null;
    }
}