<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory;

    protected $table = 'equipment';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'description',
        'serial_number',
        'responsible',
        'status',
        'registration_date',
        'company_id',
        'location_id',
        'image',
        'model'
    ];
}
