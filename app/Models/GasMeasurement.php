<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GasMeasurement extends Model
{
    protected $table = 'gas_measurement';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'measurement_date',
        'measurement_time',
        'chief_signature',
        'sst_signature',
    ];

    public function detail(): HasMany
    {
        return $this->hasMany(GasMeasurementDetail::class, 'gas_measurement_id');
    }

    /**
     * Qué le falta al formato para estar completo (todos sus campos son obligatorios):
     * hora de ingreso, las dos firmas y todos los campos de cada ubicación.
     */
    public function pendientes(): array
    {
        $detalles = $this->detail()->with('location:id,name')->orderBy('id')->get();

        $cabecera = [];
        if (blank($this->measurement_time)) {
            $cabecera[] = 'la hora de ingreso';
        }
        if (blank($this->chief_signature)) {
            $cabecera[] = 'la firma del jefe de mina';
        }
        if (blank($this->sst_signature)) {
            $cabecera[] = 'la firma del líder SST';
        }

        return [
            'cabecera' => $cabecera,
            'ubicaciones' => $detalles->filter(fn ($d) => $d->incompleto())
                ->map(fn ($d) => $d->location?->name ?? "ubicación #{$d->location_id}")
                ->values()
                ->all(),
            'total_ubicaciones' => $detalles->count(),
        ];
    }

    public function estaCompleta(): bool
    {
        $pendientes = $this->pendientes();

        return empty($pendientes['cabecera']) && empty($pendientes['ubicaciones']);
    }

    protected function chiefSignature(): Attribute
    {
        return Attribute::make(get: fn ($valor) => $this->leerBinario($valor));
    }

    protected function sstSignature(): Attribute
    {
        return Attribute::make(get: fn ($valor) => $this->leerBinario($valor));
    }

    private function leerBinario($valor)
    {
        if (!is_resource($valor)) {
            return $valor;
        }
        rewind($valor);
        return stream_get_contents($valor);
    }
}