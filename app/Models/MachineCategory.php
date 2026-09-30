<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MachineCategory extends Model
{
    use HasFactory;

    protected $table = 'machine_category';

    public $timestamps = false;
    
    protected $fillable = [
        'name',
        'description',
        'status'
    ];
    
}
