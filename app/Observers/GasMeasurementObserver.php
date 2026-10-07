<?php

namespace App\Observers;

use App\Models\GasMeasurement;
use App\Services\AlertaService;
use Illuminate\Database\Eloquent\Model;

class GasMeasurementObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'gas'; }
    protected function etiqueta(Model $model): string { return "Medición en {$model->location}"; }
    protected function rolDestino(): ?string { return 'admin'; }

    public function created(Model $model): void
    {
        /** @var GasMeasurement $model */
        $limites = config('alertas.gas');
        $riesgos = [];

        if ($model->oxygen !== null && ($model->oxygen < $limites['oxygen']['min'] || $model->oxygen > $limites['oxygen']['max'])) {
            $riesgos[] = "Oxígeno fuera de rango ({$model->oxygen}%)";
        }
        if ($model->methane !== null && $model->methane > $limites['methane']['max']) {
            $riesgos[] = "Metano elevado ({$model->methane}%)";
        }
        if ($model->carbon_monoxide !== null && $model->carbon_monoxide > $limites['carbon_monoxide']['max']) {
            $riesgos[] = "Monóxido de carbono elevado ({$model->carbon_monoxide})";
        }
        if ($model->hydrogen_sulfide !== null && $model->hydrogen_sulfide > $limites['hydrogen_sulfide']['max']) {
            $riesgos[] = "Ácido sulfhídrico elevado ({$model->hydrogen_sulfide})";
        }
        if ($model->nitrogen_dioxide !== null && $model->nitrogen_dioxide > $limites['nitrogen_dioxide']['max']) {
            $riesgos[] = "Dióxido de nitrógeno elevado ({$model->nitrogen_dioxide})";
        }

        AlertaService::porRol(
            'admin',
            $this->tipo(),
            $riesgos ? "Riesgo detectado en {$model->location}: " . implode('; ', $riesgos)
            : "Medición de gases en {$model->location} dentro de rangos normales",
            $riesgos ? 'danger' : 'info'
        );
    }

    public function updated(Model $model): void {}
    public function deleted(Model $model): void {}
}