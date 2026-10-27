<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class CompanyObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'empresa'; }
    protected function etiqueta(Model $model): string { return "Empresa {$model->name}"; }

    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        return '/companies';
    }
}