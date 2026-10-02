<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $table = 'machine';
    public $timestamps = false;

    protected $fillable = [
        'code', 'name', 'model', 'serial_number', 'acquisition_date',
        'acquisition_cost', 'status', 'warranty', 'image', 'location_id',
        'machine_category_id', 'company_id', 'responsible', 'machine_usage',
        'in_operation', 'characteristics',
    ];

    public function location()
    {
    return $this->belongsTo(Location::class);
    }

    public function machineCategory()
    {
    return $this->belongsTo(MachineCategory::class);
    }
}