<?php

namespace App\Observers;

use App\Services\AlertaService;
use Illuminate\Database\Eloquent\Model;

abstract class AlertaObserverBase
{
    abstract protected function tipo(): string;
    abstract protected function etiqueta(Model $model): string;

    protected function rolDestino(): ?string
    {
        return null;
    }

    public function created(Model $model): void
    {
        $this->notificar("Se registró: {$this->etiqueta($model)}", 'info');
    }

    public function updated(Model $model): void
    {
        if ($model->isDirty('status')) {
            $nivel = match ($model->status) {
                'inactive' => 'danger',
                'maintenance' => 'warning',
                default => 'info',
            };
            $this->notificar("{$this->etiqueta($model)} cambió a estado: {$model->status}", $nivel);
        }
    }

    public function deleted(Model $model): void
    {
        $this->notificar("Se eliminó: {$this->etiqueta($model)}", 'warning');
    }

    private function notificar(string $mensaje, string $nivel): void
    {
        $rol = $this->rolDestino();
        $rol
            ? AlertaService::porRol($rol, $this->tipo(), $mensaje, $nivel)
            : AlertaService::todos($this->tipo(), $mensaje, $nivel);
    }
}