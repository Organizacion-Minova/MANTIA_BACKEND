<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class InspectionObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'inspeccion'; }
    protected function etiqueta(Model $model): string
    {
        return "Inspección de {$model->equipment_name} por {$model->reviewed_by}";
    }

    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        return $model->machine_id ? "/machines/cardMachine/{$model->machine_id}" : '/machines';
    }

    public function updated(Model $model): void {}
    public function deleted(Model $model): void {}
}