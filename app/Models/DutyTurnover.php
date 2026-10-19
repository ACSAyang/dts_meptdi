<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DutyTurnover extends Model
{
    protected $fillable = [
        'from_duty_period_id', 'from_user_id', 'to_duty_period_id', 'to_user_id',
        'turned_over_at', 'notes', 'pending_snapshot', 'acknowledged_at', 'acknowledgment_remarks',
    ];

    protected function casts(): array
    {
        return [
            'turned_over_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'pending_snapshot' => 'array',
        ];
    }

    /** The latest turnover still waiting for the incoming watch to acknowledge it. */
    public static function awaitingAcknowledgment(): ?self
    {
        return static::with('fromUser')->whereNull('acknowledged_at')->latest('turned_over_at')->first();
    }

    public function fromPeriod(): BelongsTo
    {
        return $this->belongsTo(DutyPeriod::class, 'from_duty_period_id');
    }

    public function toPeriod(): BelongsTo
    {
        return $this->belongsTo(DutyPeriod::class, 'to_duty_period_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
