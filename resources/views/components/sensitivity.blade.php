@props(['record'])
@php
    $colors = [
        'unclassified' => 'bg-slate-50 text-slate-500 ring-1 ring-slate-200',
        'restricted' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-300',
        'confidential' => 'bg-red-50 text-red-800 ring-1 ring-red-300',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'badge uppercase tracking-wider '.($colors[$record->sensitivity] ?? '')]) }}>
    @if ($record->sensitivity !== 'unclassified')<x-icon name="lock" class="size-3" />@endif
    {{ $record->sensitivityLabel() }}
</span>
