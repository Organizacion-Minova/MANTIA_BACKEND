<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GasMeasurementDetail extends Model
{
    protected $table = 'gas_measurement_detail';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'gas_measurement_id',
        'location_id',
        'oxygen',
        'methane',
        'carbon_dioxide',
        'hydrogen_sulfide',
        'carbon_monoxide',
        'nitrogen_dioxide',
        'responsible_name',
        'observation',
    ];

    public function gasMeasurement(): BelongsTo
    {
        return $this->belongsTo(GasMeasurement::class, 'gas_measurement_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}