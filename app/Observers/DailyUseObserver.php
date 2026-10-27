<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

/**
 * Alertas del formato de uso diario de las máquinas.
 * Al hacer clic, la alerta lleva a la ficha de la máquina.
 */
class DailyUseObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'uso_diario'; }

    protected function etiqueta(Model $model): string
    {
        $maquina = $model->machine?->name ?? "máquina #{$model->machine_id}";
        $fecha = $model->date instanceof \DateTimeInterface ? $model->date->format('Y-m-d') : $model->date;

        return "Uso diario de {$maquina} ({$fecha})";
    }

    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        return $model->machine_id ? "/machines/cardMachine/{$model->machine_id}" : '/dailyuse';
    }
}