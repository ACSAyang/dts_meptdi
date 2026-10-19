<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $old = null,
        ?array $new = null,
        ?string $reason = null,
        ?User $user = null,
    ): AuditLog {
        $user ??= auth()->user();
        $request = request();

        return AuditLog::create([
            'user_id' => $user?->id,
            'duty_period_id' => $user ? Duty::periodFor($user)?->id : null,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'reference_no' => $subject?->getAttribute('reference_no'),
            'description' => $description,
            'old_values' => $old,
            'new_values' => $new,
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }
}
