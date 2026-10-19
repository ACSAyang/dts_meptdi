<?php

namespace App\Models;

use App\Models\Concerns\IsTrackedCommunication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutgoingCommunication extends Model
{
    use IsTrackedCommunication;

    public const REF_TYPE = 'OUT';

    /** Fields that may be corrected after registration (with a reason). */
    public const CORRECTABLE = [
        'document_date', 'document_type', 'subject', 'originating_office', 'signatory', 'destination_office',
        'addressee', 'destination_address', 'sensitivity', 'priority', 'mode_of_dispatch', 'enclosures',
        'remarks', 'liaison_name',
    ];

    protected array $searchable = ['reference_no', 'subject', 'destination_office', 'addressee', 'liaison_name', 'received_by_name', 'signatory'];

    protected $fillable = [
        'reference_no', 'ref_year', 'ref_sequence', 'document_date', 'document_type', 'subject',
        'originating_office', 'signatory', 'destination_office', 'addressee', 'destination_address',
        'sensitivity', 'priority', 'mode_of_dispatch', 'enclosures', 'remarks', 'liaison_name',
        'dispatched_at', 'handed_over_at', 'handover_remarks', 'status', 'received_by_name',
        'received_by_position', 'received_at', 'receipt_remarks', 'verified_at', 'verified_by',
        'verification_remarks', 'closed_at', 'closed_by', 'cancelled_at', 'cancelled_by', 'cancel_reason',
        'registered_by', 'duty_period_id',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'dispatched_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'received_at' => 'datetime',
            'verified_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class)->orderBy('attempt_no');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function statusLabel(): string
    {
        return config('dts.outgoing_statuses')[$this->status] ?? $this->status;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['closed', 'cancelled']);
    }
}
