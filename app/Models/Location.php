<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'location';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = false;  

    protected $fillable = [
        'name',
        'description',
        'status',
        'location_category_id'
    ];

    public function locationCategory()
    {
        return $this->belongsTo(LocationCategory::class, 'location_category_id');
    }
}
