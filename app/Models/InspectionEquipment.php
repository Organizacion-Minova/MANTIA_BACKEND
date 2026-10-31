<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionEquipment extends Model
{
    protected $table = 'inspection_equipment';
    public $timestamps = false;

    protected $fillable = [
        'format_id', 
        'week_start', 
        'week_end', 
        'header_data', 
        'general_observations'
    ];
    protected $casts = ['header_data' => 'array'];

    public function format() { 
        return $this->belongsTo(InspectionFormatEquipment::class, 'format_id'); 
    }
    public function answers() { 
        return $this->hasMany(InspectionAnswerEquipment::class, 'inspection_id'); 
    }
    public function signatures() { 
        return $this->hasMany(InspectionSignatureEquipment::class, 'inspection_id'); 
    }
}
