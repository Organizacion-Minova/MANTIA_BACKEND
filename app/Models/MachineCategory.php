<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineCategory extends Model
{
    protected $table = 'machine_category';
    public $timestamps = false;
    protected $fillable = ['name', 'description', 'status'];
}