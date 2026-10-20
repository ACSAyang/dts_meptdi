<x-layouts.app title="Pending-Action Report">
    @include('reports.partials.header', ['heading' => 'Pending-Action Report', 'subheading' => (request()->boolean('overdue') ? 'Overdue incoming communications' : 'Open incoming communications').' as of '.now()->format('d M Y H:i').'H'])

    <div class="no-print mb-4 flex gap-2">
        <a href="{{ route('reports.pending') }}" class="btn btn-sm {{ request()->boolean('overdue') ? 'btn-secondary' : 'btn-primary' }}">All pending ({{ request()->boolean('overdue') ? '…' : $incoming->count() }})</a>
        <a href="{{ route('reports.pending', ['overdue' => 1]) }}" class="btn btn-sm {{ request()->boolean('overdue') ? 'btn-primary' : 'btn-secondary' }}">Overdue only</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table" data-datatable data-paging="false" data-empty="No pending actions.">
                <thead><tr><th>Reference</th><th>Received</th><th>Sender</th><th>Subject</th><th>Action required</th><th>Routed to</th><th>Due</th><th>Status</th><th>Age</th></tr></thead>
                <tbody>
                @foreach ($incoming as $r)
                    <tr>
                        <td class="whitespace-nowrap"><a href="{{ route('incoming.show', $r) }}" class="ref hover:underline">{{ $r->reference_no }}</a></td>
                        <td class="text-xs whitespace-nowrap" data-order="{{ $r->received_at->timestamp }}">{{ $r->received_at->format('d M Y H:i') }}</td>
                        <td class="text-xs">{{ $r->sender_name }}</td>
                        <td class="text-xs">{{ \Illuminate\Support\Str::limit($r->subject, 90) }}</td>
                        <td class="text-xs">{{ \Illuminate\Support\Str::limit($r->action_required, 70) }}</td>
                        <td class="text-xs">{{ $r->routed_to ?? 'Not routed' }}</td>
                        <td class="text-xs whitespace-nowrap {{ $r->isOverdue() ? 'font-semibold text-red-600' : '' }}">{{ $r->action_due_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-xs whitespace-nowrap">{{ $r->statusLabel() }}{{ $r->isOverdue() ? ' · OVERDUE' : '' }}</td>
                        <td class="text-xs whitespace-nowrap">{{ (int) $r->received_at->diffInDays(now()) }} d</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @include('reports.partials.signatures')
</x-layouts.app>
