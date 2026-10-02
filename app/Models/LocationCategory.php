<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationCategory extends Model
{
    protected $table = 'location_category';
    public $timestamps = false;
    protected $fillable = ['name', 'description'];
}