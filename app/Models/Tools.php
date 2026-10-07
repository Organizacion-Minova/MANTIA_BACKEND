<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Tools extends Model
{
    use HasFactory;

    protected $table='tool';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'location_id',
        'category_id'
    ];
    protected $appends = ['stock_total'];

    public function categoria()
    {
        return $this->belongsTo(CategoryTools::class, 'category_id');
    }
    public function ubicacion()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
    public function condiciones()
    {
        return $this->hasMany(ToolStockCondition::class, 'tool_id');
    }
    public function getStockTotalAttribute()
    {
        return $this->condiciones->sum('stock');
    }
    
}
