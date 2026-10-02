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

    public function updated(Model $model): void {}
    public function deleted(Model $model): void {}
}