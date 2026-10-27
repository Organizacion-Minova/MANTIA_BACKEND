<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionQuestion extends Model
{
    use HasFactory;
    
    protected $table = 'inspection_question';

    public $timestamps = false;

    protected $fillable = [
        'machine_id', 
        'text', 
        'type',
        'observation', 
        'position', 
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }
}
