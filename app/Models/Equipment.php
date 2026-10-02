<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipment';
    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'description', 'serial_number', 'responsible',
        'status', 'registration_date', 'company_id', 'location_id',
        'image', 'model',
    ];
}