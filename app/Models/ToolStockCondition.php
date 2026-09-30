<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Tools;

class ToolStockCondition extends Model
{
    use HasFactory;

    protected $table = 'tool_stock_condition';

    public $timestamps = false;

    protected $fillable = [
        'tool_id',
        'condition',
        'stock',
    ];

    public function tool()
    {
        return $this->belongsTo(Tools::class, 'tool_id');
    }
}
