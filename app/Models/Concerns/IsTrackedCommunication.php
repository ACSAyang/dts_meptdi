<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use App\Models\DutyPeriod;
use App\Models\RecordEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared behaviour of incoming and outgoing communications:
 * timeline, attachments and accountability links. The sensitivity level is a handling
 * label only: every user can open every record.
 */
trait IsTrackedCommunication
{
    public function events(): MorphMany
    {
        return $this->morphMany(RecordEvent::class, 'trackable')->orderBy('occurred_at')->orderBy('id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function dutyPeriod(): BelongsTo
    {
        return $this->belongsTo(DutyPeriod::class);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['closed', 'cancelled'], true);
    }

    public function sensitivityLabel(): string
    {
        return config('dts.sensitivities')[$this->sensitivity] ?? $this->sensitivity;
    }

    public function priorityLabel(): string
    {
        return config('dts.priorities')[$this->priority] ?? $this->priority;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            foreach ($this->searchable as $column) {
                $q->orWhere($column, 'like', '%'.$term.'%');
            }
        });
    }
}
