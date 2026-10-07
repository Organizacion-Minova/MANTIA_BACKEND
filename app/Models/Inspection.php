<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    protected $table = 'inspection';
    public $timestamps = false;

    protected $fillable = [
        'inspection_date', 'duration', 'area', 'equipment_name',
        'reviewed_by', 'review_date', 'review_table', 'observations',
        'signature', 'machine_id',
    ];
}