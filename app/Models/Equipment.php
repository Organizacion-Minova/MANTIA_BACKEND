<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

    public const CATEGORY_CONSUMABLE = 'Consumible';
    public const CATEGORY_NON_CONSUMABLE = 'NoConsumible';

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
        'category',
    ];
    public function ubicacion()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
