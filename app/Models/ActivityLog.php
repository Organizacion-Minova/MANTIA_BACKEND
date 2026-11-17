<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Un cambio del historial (una "confirmación" en la vista tipo ramas). */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'extra' => 'array',
            'revertible' => 'boolean',
            'reverted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function revertidoPor()
    {
        return $this->belongsTo(User::class, 'reverted_by');
    }
}
