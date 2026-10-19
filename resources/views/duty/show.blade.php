<x-layouts.app title="Duty Log">
    <div class="no-print mb-4 flex items-center justify-between">
        <a href="{{ route('duty.index') }}" class="inline-flex items-center gap-1 text-sm text-navy-600 hover:text-navy-900"><x-icon name="arrow-left" /> Duty Office Watch</a>
        <button data-print class="btn btn-secondary"><x-icon name="print" /> Print duty log</button>
    </div>

    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Duty Office Watch Log</h2>
                <span class="badge bg-navy-50 text-navy-700">{{ $duty->statusLabel() }}</span>
            </div>
            <dl class="dl grid gap-x-8 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <div><dt>Duty Office Watch</dt><dd class="font-semibold">{{ $duty->user->displayName() }}</dd></div>
                <div><dt>Scheduled</dt><dd>{{ $duty->scheduled_start->format('d M Y H:i') }} – {{ $duty->scheduled_end->format('d M Y H:i') }}H</dd></div>
                <div><dt>Started / Ended</dt><dd>{{ $duty->started_at?->format('d M Y H:i') ?? '—' }} / {{ $duty->ended_at?->format('d M Y H:i') ?? '—' }}</dd></div>
                <div><dt>Assigned by</dt><dd>{{ $duty->assigner?->displayName() ?? '—' }}</dd></div>
                @if ($duty->cancel_reason)<div class="sm:col-span-4"><dt>Cancellation reason</dt><dd>{{ $duty->cancel_reason }}</dd></div>@endif
            </dl>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Turnover received</h2></div>
                <div class="p-5 text-sm">
                    @if ($t = $duty->turnoverReceived)
                        <div class="text-xs text-slate-500">From {{ $t->fromUser->displayName() }} · {{ $t->turned_over_at->format('d M Y H:i') }}H · acknowledged {{ $t->acknowledged_at?->format('d M Y H:i') }}H</div>
                        <div class="mt-2 whitespace-pre-line">{{ $t->notes }}</div>
                        @if ($t->acknowledgment_remarks)<div class="mt-2 text-xs text-slate-600"><strong>Acknowledgment remarks:</strong> {{ $t->acknowledgment_remarks }}</div>@endif
                    @else
                        <span class="text-slate-400">No turnover received (first duty period or none recorded).</span>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h2 class="card-title">Turnover given</h2></div>
                <div class="space-y-3 p-5 text-sm">
                    @if ($t = $duty->turnoverGiven)
                        <div class="text-xs text-slate-500">{{ $t->turned_over_at->format('d M Y H:i') }}H · {{ $t->toUser ? 'acknowledged by '.$t->toUser->displayName().' at '.$t->acknowledged_at->format('d M Y H:i').'H' : 'awaiting acknowledgment' }}</div>
                        <div class="whitespace-pre-line">{{ $t->notes }}</div>
                        @include('duty.partials.snapshot', ['snapshot' => $t->pending_snapshot])
                    @else
                        <span class="text-slate-400">Not yet turned over.</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Transactions recorded during this duty ({{ $events->count() }})</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Time</th><th>Reference</th><th>Transaction</th><th>Details</th><th>By</th></tr></thead>
                    <tbody>
                    @forelse ($events as $e)
                        @php $rec = $e->trackable; @endphp
                        <tr>
                            <td class="text-xs whitespace-nowrap">{{ $e->occurred_at->format('d M H:i') }}</td>
                            <td class="whitespace-nowrap">
                                @if ($rec)
                                    <a href="{{ route($rec instanceof \App\Models\IncomingCommunication ? 'incoming.show' : 'outgoing.show', $rec) }}" class="ref hover:underline">{{ $rec->reference_no }}</a>
                                @endif
                            </td>
                            <td class="text-xs font-medium">{{ $e->label() }}</td>
                            <td class="text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($e->description, 120) }}</td>
                            <td class="text-xs">{{ $e->user->displayName() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">No transactions recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="print-only mt-12">
            <div class="grid grid-cols-2 gap-16 text-center text-xs">
                <div><div class="border-t border-black pt-1">Outgoing Duty Office Watch</div></div>
                <div><div class="border-t border-black pt-1">Incoming Duty Office Watch</div></div>
            </div>
        </div>
    </div>
</x-layouts.app>
