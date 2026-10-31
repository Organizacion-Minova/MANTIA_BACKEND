<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionFormatEquipment extends Model
{
    protected $table = 'inspection_format_equipment';
    public $timestamps = false;

    protected $fillable = [
        'equipment_id', 
        'code', 
        'version', 
        'valid_from',
        'numbered',
        'has_general_observations',
        'is_active',
        'header_fields',
        'signers',
    ];

    protected $casts = [
        'header_fields' => 'array',
        'is_active' => 'boolean',
        'signers' => 'array',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function sections()
    {
        return $this->hasMany(InspectionSectionEquipment::class, 'format_id')->orderBy('position');
    }
    public function inspections() 
    { 
        return $this->hasMany(InspectionEquipment::class, 'format_id'); 
    }
}
