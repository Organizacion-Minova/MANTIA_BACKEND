<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreoperationalMachine extends Model
{
    protected $table = 'preoperational_machine';
    public $timestamps = false;

    protected $fillable = [
        'machine_id', 
        'format_id', 
        'week_start', 
        'week_end', 
        'inspector', 
        'area',
        'duration_minutes', 
        'reviewed_by', 
        'review_date', 
        'general_observations', 
        'signature',
    ];

    public function machine() { 
        return $this->belongsTo(Machine::class, 'machine_id'); 
    }

    public function format() { 
        return $this->belongsTo(InspectionFormatMachine::class, 'format_id'); 
    }

    public function answers() { 
        return $this->hasMany(PreoperationalAnswerMachine::class, 'inspection_id'); 
    }
}
