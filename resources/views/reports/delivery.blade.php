<x-layouts.app title="Delivery Report">
    @include('reports.partials.header', ['heading' => 'Outgoing Delivery Report', 'subheading' => $from->format('d M Y').' – '.$to->format('d M Y')])

    <form class="no-print card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div><label class="label">From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
        <div><label class="label">To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
        <div><label class="label">Status</label>
            <select name="status" class="input">
                <option value="">All</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending (open)</option>
                @foreach (config('dts.outgoing_statuses') as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <button class="btn btn-primary">Show</button>
    </form>

    @php $byStatus = $records->countBy('status'); @endphp
    <div class="mb-4 flex flex-wrap gap-2 text-xs">
        <span class="badge bg-navy-800 text-white">Total {{ $records->count() }}</span>
        @foreach (config('dts.outgoing_statuses') as $k => $l)
            @if ($byStatus[$k] ?? 0)<span class="badge bg-navy-50 text-navy-800">{{ $l }}: {{ $byStatus[$k] }}</span>@endif
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Reference</th><th>Subject</th><th>Destination</th><th>Liaison</th><th>Dispatched</th><th>Attempts</th><th>Received by</th><th>Received</th><th>Verified</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($records as $r)
                    <tr>
                        <td class="whitespace-nowrap"><a href="{{ route('outgoing.show', $r) }}" class="ref hover:underline">{{ $r->reference_no }}</a></td>
                        <td class="text-xs">{{ \Illuminate\Support\Str::limit($r->subject, 70) }}</td>
                        <td class="text-xs">{{ $r->destination_office }}</td>
                        <td class="text-xs">{{ $r->liaison_name }}</td>
                        <td class="text-xs whitespace-nowrap">{{ $r->dispatched_at?->format('d M H:i') }}</td>
                        <td class="text-xs text-center">{{ $r->delivery_attempts_count }}</td>
                        <td class="text-xs">{{ $r->received_by_name }}</td>
                        <td class="text-xs whitespace-nowrap">{{ $r->received_at?->format('d M H:i') }}</td>
                        <td class="text-xs whitespace-nowrap">{{ $r->verified_at?->format('d M H:i') }}</td>
                        <td class="text-xs whitespace-nowrap">{{ $r->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="py-8 text-center text-slate-400">No outgoing communications in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @include('reports.partials.signatures')
</x-layouts.app>
