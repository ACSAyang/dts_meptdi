@props(['steps'])
{{-- Progress steps: [[label, done(bool), time(?Carbon)], ...] --}}
<ol class="grid border-b border-navy-50 px-2 py-4" style="grid-template-columns: repeat({{ count($steps) }}, minmax(0, 1fr))">
    @foreach ($steps as [$label, $done, $at])
        <li class="flex flex-col items-center text-center">
            <span class="flex size-7 items-center justify-center rounded-full text-xs font-semibold {{ $done ? 'bg-navy-800 text-white' : 'bg-navy-50 text-navy-300 ring-1 ring-navy-100' }}">
                @if ($done)<x-icon name="check" class="size-3.5" />@else{{ $loop->iteration }}@endif
            </span>
            <span class="mt-1.5 text-[11px] font-semibold {{ $done ? 'text-navy-900' : 'text-slate-400' }}">{{ $label }}</span>
            <span class="text-[10px] text-slate-500 tabular-nums">{{ $at?->format('d M H:i') }}</span>
        </li>
    @endforeach
</ol>
