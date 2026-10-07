<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationCategory extends Model
{
    protected $table = 'location_category';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = false;  

    protected $fillable = [
        'name',
        'description',
    ];
}
