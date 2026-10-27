<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionAnswerEquipment extends Model
{
    use HasFactory;
    protected $table = 'inspection_answer_equipment';

    public $timestamps = false;

    protected $fillable = [
        'inspection_id',
        'question_id',
        'answer',
        'observation',
    ];

    public function inspection()
    {
        return $this->belongsTo(InspectionEquipment::class, "inspection_id");
    }

    public function question()
    {
        return $this->belongsTo(InspectionQuestionEquipment::class, 'question_id');
    }
}
