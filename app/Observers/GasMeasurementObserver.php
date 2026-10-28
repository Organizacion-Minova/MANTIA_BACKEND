<?php

namespace App\Observers;

use App\Models\GasMeasurement;
use App\Models\GasMeasurementDetail;
use App\Services\AlertaService;
use Illuminate\Database\Eloquent\Model;

/**
 * Alertas de la medición de gases.
 *
 * Se registra para los dos modelos (ver AppServiceProvider):
 *  - GasMeasurement        → el encabezado del formato del día (fecha y firmas).
 *  - GasMeasurementDetail  → los valores de cada gas por ubicación.
 *
 * Desde la migración 2026_10_08 los valores de los gases están en gas_measurement_detail,
 * por eso la revisión de límites se hace sobre el detalle.
 */
class GasMeasurementObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'gas'; }

    protected function etiqueta(Model $model): string
    {
        if ($model instanceof GasMeasurementDetail) {
            return "Medición en " . ($model->location?->name ?? "ubicación #{$model->location_id}");
        }
        return "Medición de gases del {$model->measurement_date}";
    }

    protected function rolDestino(): array|string|null { return ['Jefe de mina', 'SST']; }

    public function created(Model $model): void
    {
        // Encabezado: solo se avisa que se abrió el formato del día
        if ($model instanceof GasMeasurement) {
            foreach (['Jefe de mina', 'SST'] as $rol) {
                AlertaService::porRol(
                    $rol,
                    $this->tipo(),
                    "Se abrió la {$this->etiqueta($model)}",
                    'info',
                    "/gases/gas_format/{$model->id}"
                );
            }
            return;
        }

        // Detalle creado con valores: se revisan todos los gases
        $this->revisarRiesgos($model, array_keys(config('alertas.gas')));
    }

    public function updated(Model $model): void
    {
        // Los valores se llenan editando el detalle; solo se revisan los gases
        // que cambiaron, para no repetir la alerta al editar la observación o el responsable
        if ($model instanceof GasMeasurementDetail) {
            $cambiados = array_keys(array_intersect_key($model->getChanges(), config('alertas.gas')));
            $this->revisarRiesgos($model, $cambiados);
        }
    }

    public function deleted(Model $model): void {}

    private function revisarRiesgos(Model $model, array $gases): void
    {
        /** @var GasMeasurementDetail $model */
        $limites = config('alertas.gas');
        $riesgos = [];

        if (in_array('oxygen', $gases) && $model->oxygen !== null && ($model->oxygen < $limites['oxygen']['min'] || $model->oxygen > $limites['oxygen']['max'])) {
            $riesgos[] = "Oxígeno fuera de rango ({$model->oxygen}%, permitido {$limites['oxygen']['min']}% a {$limites['oxygen']['max']}%)";
        }
        if (in_array('methane', $gases) && $model->methane !== null && $model->methane > $limites['methane']['max']) {
            $riesgos[] = "Metano elevado ({$model->methane}%, máximo {$limites['methane']['max']}%)";
        }
        if (in_array('carbon_dioxide', $gases) && $model->carbon_dioxide !== null && $model->carbon_dioxide > $limites['carbon_dioxide']['max']) {
            $riesgos[] = "Dióxido de carbono elevado ({$model->carbon_dioxide}%, máximo {$limites['carbon_dioxide']['max']}%)";
        }
        if (in_array('carbon_monoxide', $gases) && $model->carbon_monoxide !== null && $model->carbon_monoxide > $limites['carbon_monoxide']['max']) {
            $riesgos[] = "Monóxido de carbono elevado ({$model->carbon_monoxide} ppm, máximo {$limites['carbon_monoxide']['max']} ppm)";
        }
        if (in_array('hydrogen_sulfide', $gases) && $model->hydrogen_sulfide !== null && $model->hydrogen_sulfide > $limites['hydrogen_sulfide']['max']) {
            $riesgos[] = "Ácido sulfhídrico elevado ({$model->hydrogen_sulfide} ppm, máximo {$limites['hydrogen_sulfide']['max']} ppm)";
        }
        if (in_array('nitrogen_dioxide', $gases) && $model->nitrogen_dioxide !== null && $model->nitrogen_dioxide > $limites['nitrogen_dioxide']['max']) {
            $riesgos[] = "Dióxido de nitrógeno elevado ({$model->nitrogen_dioxide} ppm, máximo {$limites['nitrogen_dioxide']['max']} ppm)";
        }

        if (empty($riesgos)) {
            return; // sin riesgo, sin alerta
        }

        $ubicacion = $model->location?->name ?? "ubicación #{$model->location_id}";

        AlertaService::porRol(
            ['Jefe de mina', 'SST'],
            $this->tipo(),
            "Riesgo detectado en {$ubicacion}: " . implode('; ', $riesgos) . '. Avisar a SST.',
            'danger'
        );
    }
}