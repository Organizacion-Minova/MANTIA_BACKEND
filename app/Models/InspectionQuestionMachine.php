<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionQuestionMachine extends Model
{
    protected $table = 'inspection_question_machine';
    public $timestamps = false;

    protected $fillable = [
        'section_id', 
        'type', 
        'text', 
        'position', 
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function section()
    {
        return $this->belongsTo(InspectionSectionMachine::class, 'section_id');
    }
}
