<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class CategoryObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'categoria'; }
    protected function etiqueta(Model $model): string { return "Categoría {$model->name}"; }

    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        return (int) $model->category_group_id === 2 ? '/noconsumables/tools/types' : '/consumables/tools/types';
    }
}