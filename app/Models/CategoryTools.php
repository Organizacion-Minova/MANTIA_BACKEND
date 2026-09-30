<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryTools extends Model
{
    use HasFactory;

    protected $table='category';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'status',
        'category_group_id'
    ];

    public function tipo()
    {
        return $this->belongsTo(CategoryGroupTools::class, 'category_group_id');
    }

}
