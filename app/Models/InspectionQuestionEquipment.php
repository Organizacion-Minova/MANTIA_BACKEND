<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionQuestionEquipment extends Model
{
    use HasFactory;
    
    protected $table = 'inspection_question_equipment';

    public $timestamps = false;

    protected $fillable = [
        'equipment_id', 
        'text', 
        'type',
        'observation', 
        'position', 
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function machine()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
