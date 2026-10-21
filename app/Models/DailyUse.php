<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyUse extends Model
{
    use HasFactory;

    protected $table='daily_use';

    public $timestamps = false;

    protected $fillable = [
        'date',
        'operating_status',
        'operation_start',
        'operation_end',
        'responsible',
        'observations',
        'machine_id'
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }
}

