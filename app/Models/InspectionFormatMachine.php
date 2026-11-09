<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionFormatMachine extends Model
{
    protected $table = 'inspection_format_machine';
    
    public $timestamps = false;

    protected $fillable = [
        'category_id', 
        'code', 
        'version', 
        'valid_from', 
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(MachineCategory::class, 'category_id');
    }

    public function sections()
    {
        return $this->hasMany(InspectionSectionMachine::class, 'format_id')->orderBy('position');
    }
}
