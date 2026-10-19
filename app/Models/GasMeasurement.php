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

    public function details(): HasMany
    {
        return $this->hasMany(GasMeasurementDetail::class, 'gas_measurement_id');
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