<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolsMachine extends Model
{
    use HasFactory;

    protected $table = 'tools_machine';

    public $timestamps = false;

    protected $fillable = [
        'tool_id',
        'brand',
        'stock',
        'observations',
        'machine_id'
    ];
    
    protected $casts = ['stock' => 'integer'];

    public function tool()
    {
        return $this->belongsTo(Tools::class, 'tool_id');
    }
    public function machine()
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

}
