@php $snapshot = $snapshot ?? []; @endphp
<div class="grid gap-4 md:grid-cols-2">
    <div>
        <div class="mb-1.5 text-[11px] font-semibold tracking-wider text-navy-500 uppercase">Pending incoming ({{ count($snapshot['incoming'] ?? []) }})</div>
        <ul class="space-y-1 text-xs">
            @forelse ($snapshot['incoming'] ?? [] as $item)
                <li><a href="{{ route('incoming.show', $item['id']) }}" class="ref text-xs hover:underline">{{ $item['reference_no'] }}</a> · {{ \Illuminate\Support\Str::limit($item['subject'], 60) }} <span class="text-slate-400">({{ $item['status'] }}{{ $item['due'] ? ', due '.$item['due'] : '' }})</span></li>
            @empty
                <li class="text-slate-400">None</li>
            @endforelse
        </ul>
    </div>
    <div>
        <div class="mb-1.5 text-[11px] font-semibold tracking-wider text-navy-500 uppercase">Pending outgoing ({{ count($snapshot['outgoing'] ?? []) }})</div>
        <ul class="space-y-1 text-xs">
            @forelse ($snapshot['outgoing'] ?? [] as $item)
                <li><a href="{{ route('outgoing.show', $item['id']) }}" class="ref text-xs hover:underline">{{ $item['reference_no'] }}</a> · {{ \Illuminate\Support\Str::limit($item['subject'], 60) }} <span class="text-slate-400">({{ $item['status'] }} → {{ $item['destination'] }})</span></li>
            @empty
                <li class="text-slate-400">None</li>
            @endforelse
        </ul>
    </div>
</div>
