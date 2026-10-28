<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    public $timestamps = false;

    protected $fillable = [
        'code', 
        'name', 
        'model',
        'serial_number', 
        'responsible',
        'status', 
        'start_up_date', 
        'brand', 
        'plate',
        'fuel',
        'power',
        'voltage',
        'location_id',
        'image', 
        'description', 
    ];
    public function ubicacion()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
