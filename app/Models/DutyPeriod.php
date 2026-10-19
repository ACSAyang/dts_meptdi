<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DutyPeriod extends Model
{
    public const SCHEDULED = 'scheduled';

    public const ACTIVE = 'active';

    public const COMPLETED = 'completed';

    public const CANCELLED = 'cancelled';

    public const STATUSES = [
        self::SCHEDULED => 'Scheduled',
        self::ACTIVE => 'On Duty',
        self::COMPLETED => 'Completed',
        self::CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'user_id', 'assigned_by', 'scheduled_start', 'scheduled_end', 'started_at',
        'ended_at', 'status', 'assignment_remarks', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start' => 'datetime',
            'scheduled_end' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** The single Duty Office Watch currently on duty, if any. */
    public static function current(): ?self
    {
        return static::with('user')->where('status', self::ACTIVE)->latest('started_at')->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function turnoverGiven(): HasOne
    {
        return $this->hasOne(DutyTurnover::class, 'from_duty_period_id');
    }

    public function turnoverReceived(): HasOne
    {
        return $this->hasOne(DutyTurnover::class, 'to_duty_period_id');
    }

    public function incoming(): HasMany
    {
        return $this->hasMany(IncomingCommunication::class);
    }

    public function outgoing(): HasMany
    {
        return $this->hasMany(OutgoingCommunication::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(RecordEvent::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function label(): string
    {
        return $this->user->displayName().' · '.$this->scheduled_start->format('d M Y H:i').' – '.$this->scheduled_end->format('d M Y H:i');
    }
}
