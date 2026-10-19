<x-layouts.app title="Duty Office Watch Report">
    @include('reports.partials.header', ['heading' => 'Duty Office Watch Report', 'subheading' => $from->format('d M Y').' – '.$to->format('d M Y')])

    <form class="no-print card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div><label class="label">From</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="input"></div>
        <div><label class="label">To</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="input"></div>
        <button class="btn btn-primary">Show</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Duty Office Watch</th><th>Started</th><th>Ended</th><th>Incoming</th><th>Outgoing</th><th>Transactions</th><th>Turned over</th><th>Acknowledged by</th></tr></thead>
                <tbody>
                @forelse ($periods as $p)
                    <tr>
                        <td><a href="{{ route('duty.show', $p) }}" class="font-medium text-navy-900 hover:underline">{{ $p->user->displayName() }}</a></td>
                        <td class="text-xs whitespace-nowrap">{{ $p->started_at->format('d M Y H:i') }}</td>
                        <td class="text-xs whitespace-nowrap">{{ $p->ended_at?->format('d M Y H:i') ?? 'On duty' }}</td>
                        <td class="text-center">{{ $p->incoming_count }}</td>
                        <td class="text-center">{{ $p->outgoing_count }}</td>
                        <td class="text-center">{{ $p->events_count }}</td>
                        <td class="text-xs whitespace-nowrap">{{ $p->turnoverGiven?->turned_over_at->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-xs">{{ $p->turnoverGiven?->toUser?->displayName() ?? ($p->turnoverGiven ? 'Pending' : '—') }}@if ($p->turnoverGiven?->acknowledged_at)<br>{{ $p->turnoverGiven->acknowledged_at->format('d M H:i') }}H @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-slate-400">No duty periods in this range.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @include('reports.partials.signatures')
</x-layouts.app>
