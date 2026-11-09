<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Machine extends Model
{
    use HasFactory;

    protected $table = 'machine';

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
        'machine_category_id',
        'image',
        'characteristics',
    ];

    public function category_maquina()
    {
        return $this->belongsTo(MachineCategory::class, 'machine_category_id');
    }
    public function ubicacion()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
    public function preoperational_inspections()
    {
        return $this->hasMany(PreoperationalMachine::class, 'machine_id');
    }
    public function consumables()
    {
        return $this->hasMany(ToolsMachine::class, 'machine_id');
    }
}
