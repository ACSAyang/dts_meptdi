<?php

namespace App\Services;

use App\Models\DutyPeriod;
use App\Models\User;

/**
 * Resolves the duty period every transaction is linked to.
 */
class Duty
{
    /**
     * Every entry is linked to the Duty Office Watch currently on duty (if any),
     * whoever records it.
     */
    public static function periodFor(User $user): ?DutyPeriod
    {
        return DutyPeriod::current();
    }
}
