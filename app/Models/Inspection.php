<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inspection extends Model
{
    use HasFactory;
    protected $table = 'inspection';
    public $timestamps = false;

    protected $fillable = [
        'machine_id',
        'inspection_date',
        'duration_minutes',
        'inspector',
        'reviewed_by',
        'review_date',
        'general_observations',
        'signature'

    ];
    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    public function answers()
    {
        return $this->hasMany(InspectionAnswer::class, 'inspection_id');
    }
}