@php
    $dots = [
        'registered' => 'bg-navy-700', 'routed' => 'bg-sky-500', 'action_update' => 'bg-amber-500', 'dispatched' => 'bg-sky-500',
        'delivery_attempt' => 'bg-orange-400', 'received' => 'bg-indigo-500', 'verified' => 'bg-emerald-600', 'closed' => 'bg-emerald-600',
        'cancelled' => 'bg-red-600', 'reopened' => 'bg-amber-600', 'corrected' => 'bg-violet-500', 'attachment' => 'bg-slate-400',
    ];
@endphp
<div class="card">
    <div class="card-header"><h2 class="card-title">Tracking history</h2></div>
    <ol class="relative space-y-5 px-5 py-5">
        @foreach ($record->events as $event)
            <li class="relative flex gap-3">
                @unless ($loop->last)<span class="absolute top-4 left-[5px] h-[calc(100%+0.5rem)] w-px bg-navy-100"></span>@endunless
                <span class="relative mt-1 size-[11px] shrink-0 rounded-full ring-4 ring-white {{ $dots[$event->event] ?? 'bg-slate-400' }}"></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                        <span class="text-sm font-semibold text-navy-900">{{ $event->label() }}@if ($event->routed_to) → {{ $event->routed_to }}@endif</span>
                        <span class="text-xs text-slate-500 tabular-nums">{{ $event->occurred_at->format('d M Y · H:i') }}H</span>
                    </div>
                    @if ($event->description)<div class="mt-0.5 text-sm whitespace-pre-line text-slate-600">{{ $event->description }}</div>@endif
                    <div class="mt-0.5 text-[11px] text-slate-400">{{ $event->user->displayName() }}</div>
                </div>
            </li>
        @endforeach
    </ol>
</div>
