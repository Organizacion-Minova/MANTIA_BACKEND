<?php

namespace App\Observers;

use App\Models\Tool;
use App\Services\AlertaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ToolObserver extends AlertaObserverBase
{
    protected function tipo(): string { return 'herramienta'; }
    protected function etiqueta(Model $model): string { return "Herramienta {$model->name}"; }

    /** Lista de herramientas de su grupo: 1 = consumibles, 2 = no consumibles. */
    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        $grupo = DB::table('category')->where('id', $model->category_id)->value('category_group_id');
        return (int) $grupo === 2 ? '/noconsumables/tools' : '/consumables/tools';
    }

    public function updated(Model $model): void
    {
        parent::updated($model);

        /** @var Tool $model */
        if ($model->isDirty('condition') && $model->condition === 'poor') {
            AlertaService::todos($this->tipo(), "{$this->etiqueta($model)} está en mal estado", 'warning');
        }

        if ($model->isDirty('stock') && $model->stock <= config('alertas.stock_minimo_herramienta')) {
            AlertaService::todos(
                $this->tipo(),
                "Stock bajo de {$model->name}: quedan {$model->stock} unidades",
                $model->stock <= 0 ? 'danger' : 'warning'
            );
        }
    }
}