<?php

namespace App\Observers;

use App\Services\AlertaService;
use Illuminate\Database\Eloquent\Model;

abstract class AlertaObserverBase
{
    /** Estados guardados en la base de datos => texto para el usuario. */
    private const ESTADOS = [
        'active'      => 'Activo',
        'inactive'    => 'Inactivo',
        'maintenance' => 'En mantenimiento',
        'approved'    => 'Aprobado',
        'pending'     => 'Pendiente',
        'rejected'    => 'Rechazado',
    ];

    abstract protected function tipo(): string;
    abstract protected function etiqueta(Model $model): string;

    protected function rolDestino(): array|string|null
    {
        return null;
    }

    protected function enlace(Model $model, bool $eliminado = false): ?string
    {
        return null;
    }

    public function created(Model $model): void
    {
        $this->notificar("Se registró: {$this->etiqueta($model)}", 'info', $this->enlace($model));
    }

    public function updated(Model $model): void
    {
        if ($model->isDirty('status')) {
            $nivel = match ($model->status) {
                'inactive' => 'danger',
                'maintenance' => 'warning',
                default => 'info',
            };
            $estado = self::ESTADOS[$model->status] ?? 'Actualizado';
            $this->notificar("{$this->etiqueta($model)} cambió a estado: {$estado}", $nivel, $this->enlace($model));
        }
    }

    public function deleted(Model $model): void
    {
        $this->notificar("Se eliminó: {$this->etiqueta($model)}", 'warning', $this->enlace($model, true));
    }

    protected function notificar(string $mensaje, string $nivel, ?string $enlace = null): void
    {
        $rol = $this->rolDestino();
        $rol
            ? AlertaService::porRol($rol, $this->tipo(), $mensaje, $nivel, $enlace)
            : AlertaService::todos($this->tipo(), $mensaje, $nivel, $enlace);
    }
}