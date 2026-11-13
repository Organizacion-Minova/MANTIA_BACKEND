<?php

namespace App\Observers;

use App\Models\GasMeasurement;
use App\Services\AlertaService;
use Illuminate\Database\Eloquent\Model;

/**
 * Alerta cuando se abre el formato diario de medición de gases.
 * Los valores por ubicación los revisa GasMeasurementDetailObserver.
 */
class GasMeasurementObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'gas'; }

    protected function etiqueta(Model $model): string
    {
        return "Medición de gases del {$model->measurement_date}";
    }

    protected function rolDestino(): array|string|null { return ['Jefe de mina', 'SST']; }

    public function created(Model $model): void
    {
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
        }
    }

    public function deleted(Model $model): void {}
}