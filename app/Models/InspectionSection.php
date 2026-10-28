<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionSection extends Model
{
    protected $table = 'inspection_section';
    public $timestamps = false;

    protected $fillable = [
        'format_id', 'title', 'position', 'mode', 'options', 'instructions', 'has_observation',
    ];

    protected $casts = [
        'options' => 'array',
        'has_observation' => 'boolean',
    ];

    public function format()
    {
        return $this->belongsTo(InspectionFormat::class, 'format_id');
    }

    public function questions()
    {
        return $this->hasMany(InspectionQuestion::class, 'section_id')->orderBy('position');
    }
}
