<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionMachine extends Model
{
    protected $table = 'inspection_machine';

    public $timestamps = false;

    protected $fillable = [
        'machine_id',
        'format_id',
        'inspection_date',
        'inspector',
        'area',
        'duration_minutes',
        'reviewed_by',
        'review_date',
        'general_observations',
        'signature'
    ];

    public function machine() {
        return $this->belongsTo(Machine::class, 'machine_id');
    }
    public function format() {
        return $this->belongsTo(InspectionFormatMachine::class, 'format_id');
    }

    public function answers() {
        return $this->hasMany(InspectionAnswerMachine::class, 'inspection_id');
    }
}
