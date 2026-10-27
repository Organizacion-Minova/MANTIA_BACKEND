<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class LocationCategoryObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'categoria'; }
    protected function etiqueta(Model $model): string { return "Categoría de ubicación {$model->name}"; }

    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        return '/ubications/categoriesubications';
    }
}