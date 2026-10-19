@props(['label', 'value', 'sub' => null, 'href' => null, 'tone' => 'navy'])
@php
    $tones = [
        'navy' => 'text-navy-900',
        'amber' => 'text-accent-600',
        'red' => 'text-red-700',
        'green' => 'text-emerald-700',
    ];
    $classes = 'card block px-5 py-4 transition'.($href ? ' hover:border-navy-300' : '');
@endphp
@if ($href)<a href="{{ $href }}" class="{{ $classes }}">@else<div class="{{ $classes }}">@endif
    <div class="text-[11px] font-semibold tracking-wider text-navy-600 uppercase">{{ $label }}</div>
    <div class="mt-1.5 text-3xl font-extrabold tabular-nums {{ $tones[$tone] ?? $tones['navy'] }}">{{ $value }}</div>
    @if ($sub)<div class="mt-1 text-xs text-slate-500">{{ $sub }}</div>@endif
@if ($href)</a>@else</div>@endif
