<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Acción que MANTIA IA propuso y que el administrador debe confirmar o cancelar.
 * Ver App\Services\AdminActions y AiActionController.
 */
class AiPendingAction extends Model
{
    use HasUuids;

    protected $table = 'ai_pending_actions';

    protected $fillable = [
        'user_id',
        'type',
        'payload',
        'summary',
        'expires_at',
        'executed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
            'executed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}