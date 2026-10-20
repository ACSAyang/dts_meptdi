<x-layouts.app title="Tracking Calendar">
    @php
        $legend = [
            'Registered' => 'registered', 'Forwarded / dispatched' => 'routed', 'Action update' => 'action_update',
            'Delivery attempt' => 'delivery_attempt', 'Received by addressee' => 'received', 'Closed / verified' => 'closed',
            'Cancelled' => 'cancelled', 'Edited / reopened' => 'corrected',
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-xl text-navy-900">Tracking Calendar</h1>
            <p class="text-sm text-slate-500">The history of every document on the day it happened. Click an entry to open the record, or a day to list everything on it.</p>
        </div>
    </div>

    <form id="calendar-filters" class="card mb-4 flex flex-wrap items-end gap-3 p-4" onsubmit="return false">
        <div class="w-full sm:w-44">
            <label for="c-type" class="label">Documents</label>
            <select id="c-type" name="type" class="input">
                <option value="">Incoming &amp; outgoing</option>
                <option value="incoming">Incoming only</option>
                <option value="outgoing">Outgoing only</option>
            </select>
        </div>
        <div class="w-full sm:w-52">
            <label for="c-step" class="label">Tracking step</label>
            <select id="c-step" name="step" class="input">
                <option value="">All steps</option>
                <option value="registered">Registered</option>
                <option value="routed,dispatched">Forwarded / dispatched</option>
                <option value="action_update,delivery_attempt">Updates &amp; delivery attempts</option>
                <option value="received">Received by addressee</option>
                <option value="closed,verified">Closed / verified</option>
                <option value="cancelled,reopened,corrected">Cancelled, reopened, edited</option>
            </select>
        </div>
        <div class="w-full sm:w-56">
            <label for="c-ref" class="label">Reference no.</label>
            <input id="c-ref" name="ref" value="{{ $reference }}" class="input" placeholder="e.g. IN-2026-0001">
        </div>
    </form>

    <div class="card p-4 sm:p-5">
        <div class="mb-4 flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-slate-600">
            @foreach ($legend as $label => $step)
                <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm" style="background: {{ \App\Http\Controllers\CalendarController::COLORS[$step] }}"></span>{{ $label }}</span>
            @endforeach
        </div>
        <div data-tracking-calendar data-source="{{ route('calendar.events') }}" data-filters="#calendar-filters"
             @if ($initialDate) data-initial-date="{{ $initialDate }}" @endif></div>
    </div>

    <div data-calendar-details class="card mt-5 hidden overflow-hidden">
        <div class="card-header"><h2 class="card-title" data-calendar-details-title></h2></div>
        <ul data-calendar-details-list class="divide-y divide-navy-50"></ul>
    </div>
</x-layouts.app>
