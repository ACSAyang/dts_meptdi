<x-layouts.app title="Dashboard">
    @php $user = auth()->user(); @endphp

    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div class="flex-1">
            <h1 class="text-xl text-navy-900">Dashboard</h1>
            <p class="text-sm text-slate-500">{{ now()->format('l, d F Y') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('incoming.create') }}" class="btn btn-accent"><x-icon name="inbox" /> Register incoming</a>
            <a href="{{ route('outgoing.create') }}" class="btn btn-primary"><x-icon name="send" /> Register outgoing</a>
        </div>
    </div>

    @unless ($current)
        <div class="mb-6 flex flex-wrap items-center gap-3 rounded-md border border-accent-400/50 bg-accent-500/10 px-5 py-3 text-sm text-accent-700">
            <x-icon name="alert" class="size-5" />
            <div class="flex-1">
                <strong>No Duty Office Watch is on duty.</strong>
                @if ($awaitingTurnover) A turnover from {{ $awaitingTurnover->fromUser->displayName() }} is waiting for acknowledgment.
                @elseif ($myNextDuty) Your next duty: {{ $myNextDuty->scheduled_start->format('d M Y H:i') }}H. @endif
            </div>
            <a href="{{ route('duty.index') }}" class="btn btn-secondary btn-sm">Duty Office Watch</a>
        </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Incoming today" :value="$stats['incoming_today']" :sub="$stats['incoming_month'].' this month'" :href="route('incoming.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()])" />
        <x-stat label="Outgoing today" :value="$stats['outgoing_today']" :sub="$stats['outgoing_month'].' this month'" :href="route('outgoing.index', ['from' => today()->toDateString(), 'to' => today()->toDateString()])" />
        <x-stat label="Pending actions" :value="$stats['pending_actions']" :sub="$stats['overdue'].' overdue'" :tone="$stats['overdue'] ? 'red' : 'amber'" :href="route('incoming.index', ['status' => 'pending'])" />
        <x-stat label="Undelivered outgoing" :value="$stats['awaiting_dispatch'] + $stats['in_transit'] + $stats['follow_up']" :sub="$stats['for_verification'].' awaiting verification'" tone="amber" :href="route('outgoing.index', ['status' => 'pending'])" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Pending incoming actions</h2>
                    <a href="{{ route('reports.pending') }}" class="text-xs font-medium text-accent-600 hover:underline">Pending-action report</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <tbody>
                        @forelse ($pendingIncoming as $r)
                            <tr>
                                <td class="whitespace-nowrap"><a href="{{ route('incoming.show', $r) }}" class="ref hover:underline">{{ $r->reference_no }}</a></td>
                                <td>{{ \Illuminate\Support\Str::limit($r->subject, 70) }}<div class="text-xs text-slate-500">{{ $r->routed_to ? 'Routed to '.$r->routed_to : 'Not yet routed' }}</div></td>
                                <td class="text-xs whitespace-nowrap {{ $r->isOverdue() ? 'font-semibold text-red-600' : 'text-slate-500' }}">{{ $r->action_due_at ? 'Due '.$r->action_due_at->format('d M H:i') : '' }}</td>
                                <td class="text-right whitespace-nowrap"><x-status :record="$r" /></td>
                            </tr>
                        @empty
                            <tr><td class="py-8 text-center text-slate-400">No pending incoming actions.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Outgoing delivery status</h2>
                    <a href="{{ route('reports.delivery') }}" class="text-xs font-medium text-accent-600 hover:underline">Delivery report</a>
                </div>
                <div class="grid grid-cols-4 divide-x divide-navy-50 border-b border-navy-50 text-center">
                    @foreach ([['To dispatch', $stats['awaiting_dispatch'], 'registered'], ['In transit', $stats['in_transit'], 'dispatched'], ['Follow-up', $stats['follow_up'], 'follow_up'], ['To verify', $stats['for_verification'], 'received']] as [$label, $count, $status])
                        <a href="{{ route('outgoing.index', ['status' => $status]) }}" class="py-3 hover:bg-navy-50/60">
                            <div class="text-xl font-semibold text-navy-900 tabular-nums">{{ $count }}</div>
                            <div class="text-[11px] text-slate-500">{{ $label }}</div>
                        </a>
                    @endforeach
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <tbody>
                        @forelse ($pendingOutgoing as $r)
                            <tr>
                                <td class="whitespace-nowrap"><a href="{{ route('outgoing.show', $r) }}" class="ref hover:underline">{{ $r->reference_no }}</a></td>
                                <td>{{ \Illuminate\Support\Str::limit($r->subject, 60) }}<div class="text-xs text-slate-500">→ {{ $r->destination_office }}{{ $r->liaison_name ? ' · '.$r->liaison_name : '' }}</div></td>
                                <td class="text-right whitespace-nowrap"><x-status :record="$r" /></td>
                            </tr>
                        @empty
                            <tr><td class="py-8 text-center text-slate-400">No outgoing communications pending.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            @if ($current)
                <div class="card px-5 py-4">
                    <div class="text-[11px] font-semibold tracking-wider text-navy-500 uppercase">Duty Office Watch</div>
                    <div class="mt-1 text-base font-bold text-navy-900">{{ $current->user->displayName() }}</div>
                    <div class="text-xs text-slate-500">On duty since {{ $current->started_at->format('d M H:i') }}H · until {{ $current->scheduled_end->format('d M H:i') }}H</div>
                    <a href="{{ route('duty.show', $current) }}" class="mt-3 inline-block text-xs font-semibold text-accent-600 hover:underline">View duty log</a>
                </div>
            @endif

            <div class="card">
                <div class="card-header"><h2 class="card-title">Recent activity</h2></div>
                <ul class="divide-y divide-navy-50">
                    @forelse ($recent as $e)
                        @php $rec = $e->trackable; @endphp
                        @continue(! $rec)
                        <li class="px-5 py-2.5">
                            <div class="flex items-baseline justify-between gap-2">
                                <a href="{{ route($rec instanceof \App\Models\IncomingCommunication ? 'incoming.show' : 'outgoing.show', $rec) }}" class="ref text-xs hover:underline">{{ $rec->reference_no }}</a>
                                <span class="text-[11px] text-slate-400 tabular-nums">{{ $e->occurred_at->format('d M H:i') }}</span>
                            </div>
                            <div class="text-xs text-slate-600">{{ $e->label() }}@if ($e->routed_to) → {{ $e->routed_to }}@endif · {{ $e->user->displayName() }}</div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-400">No activity yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
