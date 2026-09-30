<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CategoryGroupTools extends Model
{
    use HasFactory;

    protected $table = "category_group";

    public $timestamps = false;

    protected $fillable = [
        'name'
    ];
}
