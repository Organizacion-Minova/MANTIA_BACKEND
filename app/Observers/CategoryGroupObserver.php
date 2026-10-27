<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class CategoryGroupObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'categoria'; }
    protected function etiqueta(Model $model): string { return "Grupo de categoría {$model->name}"; }

    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        return (int) $model->id === 2 ? '/noconsumables/tools/types' : '/consumables/tools/types';
    }
}