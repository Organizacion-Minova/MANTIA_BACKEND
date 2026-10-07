<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class LocationObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'ubicacion'; }
    protected function etiqueta(Model $model): string { return "Ubicación {$model->name}"; }
}