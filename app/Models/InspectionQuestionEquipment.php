<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionQuestionEquipment extends Model
{
    protected $table = 'inspection_question_equipment';
    public $timestamps = false;

    protected $fillable = ['section_id', 'text', 'position', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function section()
    {
        return $this->belongsTo(InspectionSectionEquipment::class, 'section_id');
    }
}
