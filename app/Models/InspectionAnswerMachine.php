<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionAnswerMachine extends Model
{
    protected $table = 'inspection_answer_machine';

    public $timestamps = false;
    
    protected $fillable = [
        'inspection_id', 
        'question_id', 
        'value',
        'observation'
    ];


    public function inspection() { 
        return $this->belongsTo(InspectionMachine::class, 'inspection_id'); 
    }
    public function question() { 
        return $this->belongsTo(InspectionQuestionMachine::class, 'question_id');
    }
}
