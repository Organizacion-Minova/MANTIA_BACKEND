<?php

namespace App\Observers;

use App\Models\GasMeasurement;
use App\Services\AlertaService;
use Illuminate\Database\Eloquent\Model;

class GasMeasurementObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'gas'; }
    protected function etiqueta(Model $model): string { return "Medición en {$model->location}"; }
    protected function rolDestino(): ?string { return 'Administrador'; }

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
            'Administrador',
            $this->tipo(),
            $riesgos ? "Riesgo detectado en {$model->location}: " . implode('; ', $riesgos)
            : "Medición de gases en {$model->location} dentro de rangos normales",
            $riesgos ? 'danger' : 'info'
        );
    if ($model->methane !== null && $model->methane > $limites['methane']['max']) {
        $riesgos[] = "Metano elevado ({$model->methane}%)";
    }
    if ($model->carbon_dioxide !== null && $model->carbon_dioxide > $limites['carbon_dioxide']['max']) {
        $riesgos[] = "Dióxido de carbono elevado ({$model->carbon_dioxide}%)";
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

    if (empty($riesgos)) {
        return; // sin riesgo, sin alerta
    }

    AlertaService::porRol(
        'Administrador',
        $this->tipo(),
        "Riesgo detectado en {$model->location}: " . implode('; ', $riesgos),
        'danger'
    );
}

    public function updated(Model $model): void {}
    public function deleted(Model $model): void {}
}