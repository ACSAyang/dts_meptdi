<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAttempt extends Model
{
    protected $fillable = [
        'attempt_no', 'attempted_at', 'liaison_name', 'result', 'remarks', 'user_id', 'duty_period_id',
    ];

    protected function casts(): array
    {
        return ['attempted_at' => 'datetime'];
    }

    public function outgoing(): BelongsTo
    {
        return $this->belongsTo(OutgoingCommunication::class, 'outgoing_communication_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resultLabel(): string
    {
        return config('dts.delivery_results')[$this->result] ?? $this->result;
    }
}
