<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryGroup extends Model
{
    protected $table = 'category_group';
    public $timestamps = false;
    protected $fillable = ['name'];
}