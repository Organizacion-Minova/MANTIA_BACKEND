<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class EquipmentObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'equipo'; }
    protected function etiqueta(Model $model): string { return "Equipo {$model->name} ({$model->code})"; }

    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        return $eliminado ? '/equipment' : "/equipment/cardequipment/{$model->id}";
    }
}