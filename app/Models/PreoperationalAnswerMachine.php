<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreoperationalAnswerMachine extends Model
{
    protected $table = 'preoperational_answer_machine';

    public $timestamps = false;

    protected $fillable = [
        'inspection_id', 
        'question_id', 
        'day', 
        'value'
    ];

    public function inspection() { 
        return $this->belongsTo(PreoperationalMachine::class, 'inspection_id'); 
    }
    public function question() { 
        return $this->belongsTo(InspectionQuestionMachine::class, 'question_id'); 
    }
}
