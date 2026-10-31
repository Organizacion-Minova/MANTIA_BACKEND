<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionSignatureEquipment extends Model
{
    protected $table = 'inspection_signature_equipment';
    public $timestamps = false;

    protected $fillable = [
        'inspection_id', 
        'role', 
        'name', 
        'id_number'
    ];
}
