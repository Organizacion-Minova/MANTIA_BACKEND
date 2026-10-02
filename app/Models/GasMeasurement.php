<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GasMeasurement extends Model
{
    protected $table = 'gas_measurement';
    public $timestamps = false;

    protected $fillable = [
        'measurement_date', 'measurement_time', 'observation', 'location',
        'oxygen', 'methane', 'carbon_dioxide', 'hydrogen_sulfide',
        'carbon_monoxide', 'nitrogen_dioxide', 'responsible_signature',
    ];
}