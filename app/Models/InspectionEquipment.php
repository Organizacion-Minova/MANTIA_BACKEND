<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionEquipment extends Model
{
    use HasFactory;

    protected $table = 'inspection_equipment';

    public $timestamps = false;

    protected $fillable = [
        'equipment_id',
        'inspector_name',
        'inspection_date',
        'inspection_time',
        'equipment_brand',
        'voltage',
        'serial_number',
        'area',
        'general_observations',
        'responsible_position',
        'responsible_signature',
        'warehouse_manager_name',
        'warehouse_manager_signature',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipment::class, "equipment_id");
    }

    public function answers()
    {
        return $this->hasMany(InspectionAnswerEquipment::class, "inspection_id");
    }
}
