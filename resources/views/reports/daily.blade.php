<x-layouts.app title="Daily Register">
    @include('reports.partials.header', ['heading' => 'Daily Communications Register', 'subheading' => $date->format('l, d F Y')])

    <form class="no-print card mb-5 flex flex-wrap items-end gap-3 p-4">
        <div><label class="label">Date</label><input type="date" name="date" value="{{ $date->toDateString() }}" class="input"></div>
        <button class="btn btn-primary">Show</button>
        <a href="{{ route('reports.daily', ['date' => $date->copy()->subDay()->toDateString()]) }}" class="btn btn-secondary">‹ Previous day</a>
        <a href="{{ route('reports.daily', ['date' => $date->copy()->addDay()->toDateString()]) }}" class="btn btn-secondary">Next day ›</a>
    </form>

    <div class="mb-4 text-sm">
        <strong class="text-navy-900">Duty Office Watch:</strong>
        @forelse ($duties as $d)
            {{ $d->user->displayName() }} ({{ $d->started_at->format('d M H:i') }}–{{ $d->ended_at?->format('d M H:i') ?? 'present' }})@if (! $loop->last); @endif
        @empty
            <span class="text-slate-500">none recorded</span>
        @endforelse
    </div>

    <div class="card mb-6 overflow-hidden">
        <div class="card-header"><h2 class="card-title">Incoming ({{ $incoming->count() }})</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Reference</th><th>Time</th><th>Sender</th><th>Type</th><th>Subject</th><th>Routed to</th><th>Received by</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($incoming as $r)
                    <tr>
                        <td class="ref whitespace-nowrap">{{ $r->reference_no }}</td>
                        <td class="text-xs">{{ $r->received_at->format('H:i') }}H</td>
                        <td class="text-xs">{{ $r->sender_name }}<br>{{ $r->sender_office }}</td>
                        <td class="text-xs">{{ $r->document_type }}</td>
                        <td class="text-xs">{{ $r->subject }}</td>
                        <td class="text-xs">{{ $r->routed_to }}</td>
                        <td class="text-xs">{{ $r->registrar->displayName() }}</td>
                        <td class="text-xs">{{ $r->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-6 text-center text-slate-400">No incoming communications.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden print:break-before-page">
        <div class="card-header"><h2 class="card-title">Outgoing ({{ $outgoing->count() }})</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Reference</th><th>Time</th><th>Destination</th><th>Type</th><th>Subject</th><th>Liaison</th><th>Received by</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($outgoing as $r)
                    <tr>
                        <td class="ref whitespace-nowrap">{{ $r->reference_no }}</td>
                        <td class="text-xs">{{ $r->created_at->format('H:i') }}H</td>
                        <td class="text-xs">{{ $r->destination_office }}<br>{{ $r->addressee }}</td>
                        <td class="text-xs">{{ $r->document_type }}</td>
                        <td class="text-xs">{{ $r->subject }}</td>
                        <td class="text-xs">{{ $r->liaison_name }}</td>
                        <td class="text-xs">{{ $r->received_by_name }}@if ($r->received_at)<br>{{ $r->received_at->format('d M H:i') }}H @endif</td>
                        <td class="text-xs">{{ $r->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-6 text-center text-slate-400">No outgoing communications.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('reports.partials.signatures')
</x-layouts.app>
