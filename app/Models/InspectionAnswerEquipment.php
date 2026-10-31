<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionAnswerEquipment extends Model
{
    protected $table = 'inspection_answer_equipment';
    public $timestamps = false;

    protected $fillable = [
        'inspection_id', 
        'question_id', 
        'day', 
        'value', 
        'observation'
    ];
}
