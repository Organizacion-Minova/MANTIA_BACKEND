<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionSectionMachine extends Model
{
    protected $table = 'inspection_section_machine';
    public $timestamps = false;

    protected $fillable = [
        'format_id', 
        'title', 
        'position'
    ];

    public function format()
    {
        return $this->belongsTo(InspectionFormatMachine::class, 'format_id');
    }

    public function questions()
    {
        return $this->hasMany(InspectionQuestionMachine::class, 'section_id')->orderBy('position');
    }
}
