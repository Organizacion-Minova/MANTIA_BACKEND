<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'company';
    protected $primaryKey = 'tax_id';
    public $incrementing = true;
    public $timestamps = false;  

    protected $fillable = [
        'nit',
        'name',
        'phone',
        'email',
        'address',
    ];
}
