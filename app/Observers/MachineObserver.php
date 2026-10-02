<?php

namespace App\Observers;

use App\Models\Machine;
use App\Services\AlertaService;
use Illuminate\Database\Eloquent\Model;

class MachineObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'maquina'; }
    protected function etiqueta(Model $model): string { return "Máquina {$model->name} ({$model->code})"; }

    public function updated(Model $model): void
    {
        parent::updated($model);

        /** @var Machine $model */
        if ($model->isDirty('in_operation') && !$model->in_operation) {
            AlertaService::todos(
                $this->tipo(),
                "{$this->etiqueta($model)} fue puesta fuera de operación",
                'warning'
            );
        }
    }
}