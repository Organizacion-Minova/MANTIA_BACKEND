<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class MachineCategoryObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'categoria'; }
    protected function etiqueta(Model $model): string { return "Categoría de máquina {$model->name}"; }
}