@props(['record'])
@php
    $colors = [
        'registered' => 'bg-slate-100 text-slate-700',
        'routed' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
        'in_action' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
        'dispatched' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
        'follow_up' => 'bg-orange-50 text-orange-800 ring-1 ring-orange-200',
        'received' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',
        'closed' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
        'cancelled' => 'bg-red-50 text-red-700 ring-1 ring-red-200 line-through',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'badge '.($colors[$record->status] ?? 'bg-slate-100 text-slate-700')]) }}>{{ $record->statusLabel() }}</span>
@if (method_exists($record, 'isOverdue') && $record->isOverdue())
    <span class="badge bg-red-600 text-white">Overdue</span>
@endif
