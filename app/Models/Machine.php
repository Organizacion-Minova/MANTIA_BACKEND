<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Machine extends Model
{
    use HasFactory;

    protected $table = 'machine';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'model',
        'serial_number',
        'acquisition_date',
        'acquisition_cost',
        'status',
        'warranty',
        'image',
        'location_id',
        'machine_category_id',
        'company_id',
        'responsible',
        'machine_usage',
        'in_operation',
        'characteristics'
    ];

    public function category_maquina()
    {
        return $this->belongsTo(MachineCategory::class, 'machine_category_id');
    }
}
