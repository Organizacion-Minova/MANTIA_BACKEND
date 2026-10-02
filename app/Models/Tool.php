<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    protected $table = 'tool';
    public $timestamps = false;
    protected $fillable = ['name', 'condition', 'stock', 'status', 'location_id', 'category_id'];
}