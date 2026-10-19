<x-layouts.app title="End Duty & Turnover">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Turnover — {{ $duty->user->displayName() }}</h2>
                <span class="text-xs text-slate-500">On duty since {{ $duty->started_at->format('d M Y · H:i') }}H</span>
            </div>
            <div class="grid grid-cols-3 divide-x divide-navy-50 border-b border-navy-50 text-center">
                <div class="py-4"><div class="text-2xl font-semibold text-navy-900">{{ $counts['incoming'] }}</div><div class="text-xs text-slate-500">Incoming registered</div></div>
                <div class="py-4"><div class="text-2xl font-semibold text-navy-900">{{ $counts['outgoing'] }}</div><div class="text-xs text-slate-500">Outgoing registered</div></div>
                <div class="py-4"><div class="text-2xl font-semibold text-navy-900">{{ $counts['events'] }}</div><div class="text-xs text-slate-500">Transactions recorded</div></div>
            </div>

            <div class="space-y-5 p-5">
                <div>
                    <div class="mb-2 text-sm font-semibold text-navy-900">Pending incoming to be turned over ({{ $pendingIncoming->count() }})</div>
                    <div class="overflow-x-auto rounded-md border border-navy-100">
                        <table class="table">
                            <thead><tr><th>Reference</th><th>Subject</th><th>Routed to</th><th>Due</th><th>Status</th></tr></thead>
                            <tbody>
                            @forelse ($pendingIncoming as $r)
                                <tr>
                                    <td class="ref whitespace-nowrap">{{ $r->reference_no }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($r->subject, 70) }}</td>
                                    <td class="text-xs">{{ $r->routed_to ?? '—' }}</td>
                                    <td class="text-xs whitespace-nowrap">{{ $r->action_due_at?->format('d M H:i') ?? '—' }}</td>
                                    <td><x-status :record="$r" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-center text-slate-400">None</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <div class="mb-2 text-sm font-semibold text-navy-900">Pending outgoing to be turned over ({{ $pendingOutgoing->count() }})</div>
                    <div class="overflow-x-auto rounded-md border border-navy-100">
                        <table class="table">
                            <thead><tr><th>Reference</th><th>Subject</th><th>Destination</th><th>Liaison</th><th>Status</th></tr></thead>
                            <tbody>
                            @forelse ($pendingOutgoing as $r)
                                <tr>
                                    <td class="ref whitespace-nowrap">{{ $r->reference_no }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($r->subject, 70) }}</td>
                                    <td class="text-xs">{{ $r->destination_office }}</td>
                                    <td class="text-xs">{{ $r->liaison_name ?? '—' }}</td>
                                    <td><x-status :record="$r" /></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-center text-slate-400">None</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <form method="POST" action="{{ route('duty.turnover.store', $duty) }}" class="space-y-4 border-t border-navy-100 pt-5" data-confirm="End this duty period and turn over? This cannot be undone.">
                    @csrf
                    <x-field label="Turnover notes" name="notes" type="textarea" rows="5" required
                             placeholder="Significant events, instructions received, items needing follow-up, equipment and keys handed over..." />
                    <label class="flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="confirm" value="1" class="mt-0.5 rounded border-navy-300">
                        I confirm the pending items above are accurate and are being turned over to the incoming Duty Office Watch.
                    </label>
                    @error('confirm')<p class="input-error">{{ $message }}</p>@enderror
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('duty.index') }}" class="btn btn-secondary">Back</a>
                        <button class="btn btn-primary">End duty &amp; turn over</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
