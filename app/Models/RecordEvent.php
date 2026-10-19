<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RecordEvent extends Model
{
    public const LABELS = [
        'registered' => 'Registered',
        'routed' => 'Routed for action',
        'action_update' => 'Action update',
        'note' => 'Note',
        'corrected' => 'Record corrected',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
        'reopened' => 'Reopened',
        'dispatched' => 'Dispatched / handed over',
        'delivery_attempt' => 'Delivery attempt',
        'received' => 'Receipt recorded',
        'verified' => 'Receipt verified',
        'attachment' => 'Attachment added',
    ];

    protected $fillable = [
        'event', 'description', 'routed_to', 'meta', 'occurred_at', 'user_id', 'duty_period_id',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function trackable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dutyPeriod(): BelongsTo
    {
        return $this->belongsTo(DutyPeriod::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->event] ?? ucfirst(str_replace('_', ' ', $this->event));
    }
}
