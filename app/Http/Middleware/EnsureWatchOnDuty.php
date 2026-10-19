<?php

namespace App\Http\Middleware;

use App\Models\DutyPeriod;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Transactions can only be recorded while a Duty Office Watch is on duty,
 * so every entry is linked to an accountable watch.
 */
class EnsureWatchOnDuty
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! DutyPeriod::current()) {
            return redirect()->route('duty.index')
                ->with('error', 'No one is on duty. Start duty first, then record the transaction.');
        }

        return $next($request);
    }
}
