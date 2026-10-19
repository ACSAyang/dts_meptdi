<x-layouts.app :title="$record->reference_no">
    @php
        $hasAction = $record->events->contains('event', 'action_update');
        $steps = [
            ['Received', true, $record->received_at],
            ['Forwarded', (bool) $record->routed_at, $record->routed_at],
            ['In action', $hasAction || $record->status === 'closed', null],
            ['Closed', $record->status === 'closed', $record->closed_at],
        ];
        // What the watch should do next on this record
        $next = match (true) {
            ! $record->isOpen() => null,
            $record->requires_action && ! $record->routed_at => 'route',
            default => 'close',
        };
    @endphp

    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('incoming.index') }}" class="inline-flex items-center gap-1 text-sm text-navy-600 hover:text-navy-900"><x-icon name="arrow-left" /> Incoming</a>
        <div class="flex gap-2">
            <a href="{{ route('incoming.edit', $record) }}" class="btn btn-secondary">Edit details</a>
            <button data-print class="btn btn-secondary"><x-icon name="print" /> Print</button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-navy-100 px-5 py-4">
                    <div>
                        <div class="text-[11px] font-semibold tracking-[0.2em] text-navy-500 uppercase">Incoming communication</div>
                        <div class="ref mt-0.5 text-lg">{{ $record->reference_no }}</div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <x-sensitivity :record="$record" />
                        <span class="badge {{ $record->priority === 'urgent' ? 'bg-red-600 text-white' : ($record->priority === 'priority' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600') }}">{{ $record->priorityLabel() }}</span>
                        <x-status :record="$record" />
                    </div>
                </div>
                @if ($record->status !== 'cancelled')<x-steps :steps="$steps" />@endif
                <div class="p-5">
                    <div class="text-[11px] font-semibold tracking-wider text-navy-500 uppercase">{{ $record->document_type }}</div>
                    <p class="mt-1 text-base leading-relaxed text-navy-950">{{ $record->subject }}</p>
                </div>
                <dl class="dl grid gap-x-8 border-t border-navy-50 px-5 pt-4 pb-1 sm:grid-cols-2 lg:grid-cols-3">
                    <div><dt>Received</dt><dd>{{ $record->received_at->format('d M Y · H:i') }}H</dd></div>
                    <div><dt>Sender</dt><dd>{{ $record->sender_name }}@if ($record->sender_office)<br><span class="text-slate-500">{{ $record->sender_office }}</span>@endif</dd></div>
                    <div><dt>Sender's reference</dt><dd>{{ $record->sender_reference ?: '—' }}</dd></div>
                    <div><dt>Document date</dt><dd>{{ $record->document_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt>Mode of receipt</dt><dd>{{ config('dts.receipt_modes')[$record->mode_of_receipt] ?? '—' }}</dd></div>
                    <div><dt>Enclosures</dt><dd>{{ $record->enclosures ?: '—' }}</dd></div>
                    <div><dt>Registered by</dt><dd>{{ $record->registrar->displayName() }}<br><span class="text-xs text-slate-500">{{ $record->created_at->format('d M Y H:i') }}H{{ $record->dutyPeriod ? ' · DOW '.$record->dutyPeriod->user->displayName() : '' }}</span></dd></div>
                    <div><dt>Routed to</dt><dd>{{ $record->routed_to ?? '—' }}@if ($record->routed_at)<br><span class="text-xs text-slate-500">{{ $record->routed_at->format('d M Y H:i') }}H</span>@endif</dd></div>
                    <div><dt>Requires action</dt><dd>{{ $record->requires_action ? 'Yes' : 'No' }}@if ($record->action_due_at)<br><span class="text-xs {{ $record->isOverdue() ? 'font-semibold text-red-600' : 'text-slate-500' }}">Due {{ $record->action_due_at->format('d M Y H:i') }}H</span>@endif</dd></div>
                    @if ($record->handling_remarks)<div class="sm:col-span-2 lg:col-span-3"><dt>Handling remarks</dt><dd class="whitespace-pre-line">{{ $record->handling_remarks }}</dd></div>@endif
                    @if ($record->action_required)<div class="sm:col-span-2 lg:col-span-3"><dt>Action required</dt><dd class="whitespace-pre-line">{{ $record->action_required }}</dd></div>@endif
                </dl>

                @if ($record->status === 'closed')
                    <div class="border-t border-emerald-100 bg-emerald-50/50 px-5 py-4">
                        <dl class="dl grid gap-x-8 sm:grid-cols-2">
                            @if ($record->action_taken)<div><dt>Action taken</dt><dd class="whitespace-pre-line">{{ $record->action_taken }}</dd></div>@endif
                            <div><dt>Outcome / disposition</dt><dd class="whitespace-pre-line">{{ $record->outcome }}</dd></div>
                            <div><dt>Closed</dt><dd>{{ $record->closed_at->format('d M Y H:i') }}H · {{ $record->closer?->displayName() }}</dd></div>
                        </dl>
                    </div>
                @elseif ($record->status === 'cancelled')
                    <div class="border-t border-red-100 bg-red-50/50 px-5 py-4 text-sm text-red-800">
                        <strong>Cancelled</strong> {{ $record->cancelled_at->format('d M Y H:i') }}H by {{ $record->canceller?->displayName() }} — {{ $record->cancel_reason }}
                    </div>
                @endif
            </div>

            @include('partials.timeline')
        </div>

        <div class="no-print space-y-6">
            @if (! \App\Models\DutyPeriod::current())
                <div class="card border-accent-400/50 p-5">
                    <div class="text-sm font-semibold text-navy-900">No one is on duty</div>
                    <p class="mt-1 text-xs text-slate-500">Start duty first to forward, update or close this record.</p>
                    <a href="{{ route('duty.index') }}" class="btn btn-accent btn-sm mt-3">Go to Duty Office Watch</a>
                </div>
            @else
            <div class="card overflow-hidden">
                @if ($next === 'route')
                    <div class="border-b border-navy-100 bg-navy-50/60 px-5 py-3">
                        <div class="text-[11px] font-bold tracking-wider text-accent-600 uppercase">Next step</div>
                        <div class="text-sm font-semibold text-navy-900">Forward to the office that will act on it</div>
                    </div>
                    <form method="POST" action="{{ route('incoming.route', $record) }}" class="space-y-3 p-5">
                        @csrf
                        <x-field label="Forward to (office / officer)" name="routed_to" required placeholder="e.g. Training Division" />
                        <x-field label="Instructions" name="instructions" type="textarea" rows="2" placeholder="Optional" />
                        <x-field label="Due date" name="action_due_at" type="datetime-local" :value="$record->action_due_at" />
                        <button class="btn btn-accent w-full">Forward</button>
                    </form>
                @elseif ($next === 'close')
                    <div class="border-b border-navy-100 bg-navy-50/60 px-5 py-3">
                        <div class="text-[11px] font-bold tracking-wider text-accent-600 uppercase">Next step</div>
                        <div class="text-sm font-semibold text-navy-900">Record the result and close</div>
                    </div>
                    <form method="POST" action="{{ route('incoming.close', $record) }}" class="space-y-3 p-5" data-confirm="Close {{ $record->reference_no }}?">
                        @csrf
                        <x-field label="Action taken" name="action_taken" type="textarea" rows="2" :required="$record->requires_action" placeholder="e.g. Reply sent to CGDNCR" />
                        <x-field label="Outcome" name="outcome" type="textarea" rows="2" required placeholder="e.g. Complied / Noted / Filed" />
                        <button class="btn btn-success w-full">Close record</button>
                    </form>
                @else
                    <div class="px-5 py-4">
                        <div class="text-sm font-semibold text-navy-900">This record is {{ strtolower($record->statusLabel()) }}.</div>
                        <div class="text-xs text-slate-500">No further action is needed.</div>
                    </div>
                @endif

                {{-- Other actions, folded away --}}
                <div class="border-t border-navy-100 bg-white">
                    <div class="px-5 pt-3 pb-1 text-[11px] font-semibold tracking-wider text-navy-500 uppercase">More actions</div>
                    @if ($record->isOpen())
                        <x-more-action title="Add a progress update">
                            <form method="POST" action="{{ route('incoming.action', $record) }}" class="space-y-3">
                                @csrf
                                <textarea name="description" rows="3" class="input" placeholder="e.g. Reply drafted; awaiting signature." required></textarea>
                                <button class="btn btn-secondary w-full">Add update</button>
                            </form>
                        </x-more-action>
                        @if ($next !== 'route')
                            <x-more-action :title="$record->routed_at ? 'Forward to another office' : 'Forward for action'">
                                <form method="POST" action="{{ route('incoming.route', $record) }}" class="space-y-3">
                                    @csrf
                                    <x-field label="Forward to (office / officer)" name="routed_to" required />
                                    <x-field label="Instructions" name="instructions" type="textarea" rows="2" />
                                    <x-field label="Due date" name="action_due_at" type="datetime-local" :value="$record->action_due_at" />
                                    <button class="btn btn-secondary w-full">Forward</button>
                                </form>
                            </x-more-action>
                        @endif
                        <x-more-action title="Cancel this record">
                            <form method="POST" action="{{ route('incoming.cancel', $record) }}" class="space-y-3" data-confirm="Cancel {{ $record->reference_no }}? The record is kept with its history.">
                                @csrf
                                <textarea name="cancel_reason" rows="2" class="input" placeholder="Reason, e.g. duplicate entry" required></textarea>
                                <button class="btn btn-danger w-full">Cancel record</button>
                            </form>
                        </x-more-action>
                    @else
                        <x-more-action title="Reopen this record">
                            <form method="POST" action="{{ route('incoming.reopen', $record) }}" class="space-y-3">
                                @csrf
                                <textarea name="reopen_reason" rows="2" class="input" placeholder="Reason for reopening" required></textarea>
                                <button class="btn btn-secondary w-full">Reopen</button>
                            </form>
                        </x-more-action>
                    @endif
                </div>
            </div>

            @endif

            @include('partials.attachments', ['uploadRoute' => route('incoming.attach', $record)])
        </div>
    </div>
</x-layouts.app>
