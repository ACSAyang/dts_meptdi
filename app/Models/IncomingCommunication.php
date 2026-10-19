<?php

namespace App\Models;

use App\Models\Concerns\IsTrackedCommunication;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IncomingCommunication extends Model
{
    use IsTrackedCommunication;

    public const REF_TYPE = 'IN';

    /** Fields that may be corrected after registration (with a reason). */
    public const CORRECTABLE = [
        'received_at', 'sender_name', 'sender_office', 'sender_reference', 'document_date', 'document_type',
        'subject', 'sensitivity', 'priority', 'mode_of_receipt', 'enclosures', 'handling_remarks',
        'requires_action', 'action_required', 'action_due_at',
    ];

    protected array $searchable = ['reference_no', 'sender_name', 'sender_office', 'sender_reference', 'subject', 'routed_to'];

    protected $fillable = [
        'reference_no', 'ref_year', 'ref_sequence', 'received_at', 'sender_name', 'sender_office',
        'sender_reference', 'document_date', 'document_type', 'subject', 'sensitivity', 'priority',
        'mode_of_receipt', 'enclosures', 'handling_remarks', 'requires_action', 'action_required',
        'action_due_at', 'routed_to', 'routed_at', 'status', 'action_taken', 'outcome', 'closed_at',
        'closed_by', 'cancelled_at', 'cancelled_by', 'cancel_reason', 'registered_by', 'duty_period_id',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'document_date' => 'date',
            'action_due_at' => 'datetime',
            'routed_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'requires_action' => 'boolean',
        ];
    }

    public function statusLabel(): string
    {
        return config('dts.incoming_statuses')[$this->status] ?? $this->status;
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->action_due_at && $this->action_due_at->isPast();
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['closed', 'cancelled']);
    }
}
