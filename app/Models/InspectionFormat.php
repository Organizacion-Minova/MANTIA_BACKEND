<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionFormat extends Model
{
    protected $table = 'inspection_format';
    public $timestamps = false;

    protected $fillable = [
        'equipment_id', 
        'code', 
        'version', 
        'valid_from',
        'header_fields',
        'is_active',
    ];

    protected $casts = [
        'header_fields' => 'array',
        'is_active' => 'boolean',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }

    public function sections()
    {
        return $this->hasMany(InspectionSection::class, 'format_id')->orderBy('position');
    }
}
