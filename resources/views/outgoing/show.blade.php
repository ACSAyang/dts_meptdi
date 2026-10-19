<x-layouts.app :title="$record->reference_no">
    @php
        $steps = [
            ['Registered', true, $record->created_at],
            ['Handed over', (bool) $record->handed_over_at, $record->handed_over_at],
            ['Dispatched', (bool) $record->dispatched_at, $record->dispatched_at],
            ['Received', (bool) $record->received_at, $record->received_at],
            ['Verified', (bool) $record->verified_at, $record->verified_at],
        ];
        // What the watch should do next on this record
        $next = match ($record->status) {
            'registered' => 'dispatch',
            'follow_up' => 'redispatch',
            'dispatched' => 'receive',
            'received' => 'verify',
            default => null,
        };
    @endphp

    <div class="no-print mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('outgoing.index') }}" class="inline-flex items-center gap-1 text-sm text-navy-600 hover:text-navy-900"><x-icon name="arrow-left" /> Outgoing</a>
        <div class="flex gap-2">
            <a href="{{ route('outgoing.edit', $record) }}" class="btn btn-secondary">Edit details</a>
            <button data-print class="btn btn-secondary"><x-icon name="print" /> Print</button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-navy-100 px-5 py-4">
                    <div>
                        <div class="text-[11px] font-semibold tracking-[0.2em] text-navy-500 uppercase">Outgoing communication</div>
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
                    <div><dt>Destination</dt><dd>{{ $record->destination_office }}@if ($record->addressee)<br><span class="text-slate-500">{{ $record->addressee }}</span>@endif</dd></div>
                    <div><dt>Mode of dispatch</dt><dd>{{ config('dts.dispatch_modes')[$record->mode_of_dispatch] ?? '—' }}</dd></div>
                    <div><dt>Liaison</dt><dd>{{ $record->liaison_name ?: '—' }}</dd></div>
                    <div><dt>Originating office</dt><dd>{{ $record->originating_office ?: '—' }}</dd></div>
                    <div><dt>Signatory</dt><dd>{{ $record->signatory ?: '—' }}</dd></div>
                    <div><dt>Document date</dt><dd>{{ $record->document_date?->format('d M Y') ?? '—' }}</dd></div>
                    <div><dt>Enclosures</dt><dd>{{ $record->enclosures ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt>Registered by</dt><dd>{{ $record->registrar->displayName() }} · {{ $record->created_at->format('d M Y H:i') }}H{{ $record->dutyPeriod ? ' · DOW '.$record->dutyPeriod->user->displayName() : '' }}</dd></div>
                    @if ($record->destination_address)<div class="sm:col-span-2 lg:col-span-3"><dt>Address</dt><dd class="whitespace-pre-line">{{ $record->destination_address }}</dd></div>@endif
                    @if ($record->remarks)<div class="sm:col-span-2 lg:col-span-3"><dt>Remarks</dt><dd class="whitespace-pre-line">{{ $record->remarks }}</dd></div>@endif
                </dl>

                @if ($record->received_by_name)
                    <div class="border-t border-indigo-100 bg-indigo-50/40 px-5 py-4">
                        <dl class="dl grid gap-x-8 sm:grid-cols-3">
                            <div><dt>Received by</dt><dd class="font-semibold">{{ $record->received_by_name }}@if ($record->received_by_position)<br><span class="font-normal text-slate-500">{{ $record->received_by_position }}</span>@endif</dd></div>
                            <div><dt>Date / time received</dt><dd>{{ $record->received_at->format('d M Y · H:i') }}H</dd></div>
                            <div><dt>Verified</dt><dd>{{ $record->verified_at ? $record->verified_at->format('d M Y H:i').'H · '.$record->verifier?->displayName() : 'Pending verification' }}</dd></div>
                            @if ($record->receipt_remarks)<div class="sm:col-span-3"><dt>Receipt remarks</dt><dd>{{ $record->receipt_remarks }}</dd></div>@endif
                        </dl>
                    </div>
                @endif
                @if ($record->status === 'cancelled')
                    <div class="border-t border-red-100 bg-red-50/50 px-5 py-4 text-sm text-red-800">
                        <strong>Cancelled</strong> {{ $record->cancelled_at->format('d M Y H:i') }}H by {{ $record->canceller?->displayName() }} — {{ $record->cancel_reason }}
                    </div>
                @endif
            </div>

            @if ($record->deliveryAttempts->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Delivery attempts</h2></div>
                    <table class="table">
                        <thead><tr><th>#</th><th>Date / time</th><th>Liaison</th><th>Result</th><th>Remarks</th></tr></thead>
                        <tbody>
                        @foreach ($record->deliveryAttempts as $a)
                            <tr>
                                <td>{{ $a->attempt_no }}</td>
                                <td class="text-xs whitespace-nowrap">{{ $a->attempted_at->format('d M Y H:i') }}H</td>
                                <td class="text-xs">{{ $a->liaison_name ?? '—' }}</td>
                                <td><span class="badge {{ $a->result === 'delivered' ? 'bg-emerald-50 text-emerald-700' : 'bg-orange-50 text-orange-800' }}">{{ $a->resultLabel() }}</span></td>
                                <td class="text-xs text-slate-600">{{ $a->remarks }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @include('partials.timeline')

            <div class="print-only mt-10">
                <div class="grid grid-cols-2 gap-16 text-center text-xs">
                    <div><div class="border-t border-black pt-1">Received by (signature over printed name / date &amp; time)</div></div>
                    <div><div class="border-t border-black pt-1">Liaison</div></div>
                </div>
            </div>
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
                @if (in_array($next, ['dispatch', 'redispatch']))
                    <div class="border-b border-navy-100 bg-navy-50/60 px-5 py-3">
                        <div class="text-[11px] font-bold tracking-wider text-accent-600 uppercase">Next step</div>
                        <div class="text-sm font-semibold text-navy-900">{{ $next === 'redispatch' ? 'Send it out again' : 'Hand it to the liaison and send it out' }}</div>
                    </div>
                    <form method="POST" action="{{ route('outgoing.dispatch', $record) }}" class="space-y-3 p-5">
                        @csrf
                        <x-field label="Liaison" name="liaison_name" :value="$record->liaison_name" :required="$record->mode_of_dispatch === 'liaison'" />
                        <x-field label="Handed to liaison" name="handed_over_at" type="datetime-local" :value="now()" required />
                        <x-field label="Left the office" name="dispatched_at" type="datetime-local" :value="now()" required />
                        <x-field label="Remarks" name="handover_remarks" type="textarea" rows="2" placeholder="Optional" />
                        <button class="btn btn-accent w-full">Record dispatch</button>
                    </form>
                @elseif ($next === 'receive')
                    <div class="border-b border-navy-100 bg-navy-50/60 px-5 py-3">
                        <div class="text-[11px] font-bold tracking-wider text-accent-600 uppercase">Next step</div>
                        <div class="text-sm font-semibold text-navy-900">Record who received it</div>
                    </div>
                    <form method="POST" action="{{ route('outgoing.receive', $record) }}" enctype="multipart/form-data" class="space-y-3 p-5">
                        @csrf
                        <x-field label="Received by (name)" name="received_by_name" required />
                        <x-field label="Position / office" name="received_by_position" />
                        <x-field label="Date / time received" name="received_at" type="datetime-local" :value="now()" required />
                        <x-field label="Remarks" name="receipt_remarks" type="textarea" rows="2" placeholder="Optional" />
                        <div>
                            <label class="label">Signed receipt / scanned proof (optional)</label>
                            <input type="file" name="proof[]" multiple class="input py-1 text-xs file:mr-2 file:rounded file:border-0 file:bg-navy-100 file:px-2 file:py-0.5 file:text-xs">
                        </div>
                        <button class="btn btn-accent w-full">Record receipt</button>
                    </form>
                @elseif ($next === 'verify')
                    <div class="border-b border-navy-100 bg-navy-50/60 px-5 py-3">
                        <div class="text-[11px] font-bold tracking-wider text-accent-600 uppercase">Next step</div>
                        <div class="text-sm font-semibold text-navy-900">Check the receipt and close</div>
                    </div>
                    <form method="POST" action="{{ route('outgoing.verify', $record) }}" class="space-y-3 p-5">
                        @csrf
                        <p class="text-xs text-slate-600">Received by <strong>{{ $record->received_by_name }}</strong> on {{ $record->received_at->format('d M Y H:i') }}H{{ $record->attachments->where('category', 'proof_of_receipt')->isNotEmpty() ? '. Proof of receipt is attached.' : '.' }}</p>
                        <label class="flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="verified" value="1" class="mt-0.5 rounded border-navy-300">
                            I have checked that the receipt details are correct.
                        </label>
                        @error('verified')<p class="input-error">{{ $message }}</p>@enderror
                        <textarea name="verification_remarks" rows="2" class="input" placeholder="Remarks (optional)"></textarea>
                        <button class="btn btn-success w-full">Verify &amp; close</button>
                    </form>
                @else
                    <div class="px-5 py-4">
                        <div class="text-sm font-semibold text-navy-900">This record is {{ strtolower($record->statusLabel()) }}.</div>
                        <div class="text-xs text-slate-500">No further action is needed.</div>
                    </div>
                @endif

                {{-- Other actions, folded away --}}
                <div class="border-t border-navy-100">
                    <div class="px-5 pt-3 pb-1 text-[11px] font-semibold tracking-wider text-navy-500 uppercase">More actions</div>
                    @if (in_array($record->status, ['dispatched', 'follow_up']))
                        <x-more-action title="Delivery not completed">
                            <form method="POST" action="{{ route('outgoing.attempt', $record) }}" class="space-y-3">
                                @csrf
                                <x-field label="Date / time" name="attempted_at" type="datetime-local" :value="now()" required />
                                <x-field label="What happened" name="result" :options="config('dts.delivery_results')" required />
                                <x-field label="Liaison's report" name="remarks" type="textarea" rows="2" />
                                <button class="btn btn-secondary w-full">Record attempt</button>
                            </form>
                        </x-more-action>
                    @endif
                    @if ($record->status === 'follow_up')
                        <x-more-action title="It was received after all">
                            <form method="POST" action="{{ route('outgoing.receive', $record) }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                <x-field label="Received by (name)" name="received_by_name" required />
                                <x-field label="Position / office" name="received_by_position" />
                                <x-field label="Date / time received" name="received_at" type="datetime-local" :value="now()" required />
                                <input type="file" name="proof[]" multiple class="input py-1 text-xs">
                                <button class="btn btn-secondary w-full">Record receipt</button>
                            </form>
                        </x-more-action>
                    @endif
                    @if ($record->isOpen())
                        <x-more-action title="Cancel this record">
                            <form method="POST" action="{{ route('outgoing.cancel', $record) }}" class="space-y-3" data-confirm="Cancel {{ $record->reference_no }}? The record is kept with its history.">
                                @csrf
                                <textarea name="cancel_reason" rows="2" class="input" placeholder="Reason, e.g. duplicate entry" required></textarea>
                                <button class="btn btn-danger w-full">Cancel record</button>
                            </form>
                        </x-more-action>
                    @else
                        <x-more-action title="Reopen this record">
                            <form method="POST" action="{{ route('outgoing.reopen', $record) }}" class="space-y-3">
                                @csrf
                                <textarea name="reopen_reason" rows="2" class="input" placeholder="Reason for reopening" required></textarea>
                                <button class="btn btn-secondary w-full">Reopen</button>
                            </form>
                        </x-more-action>
                    @endif
                </div>
            </div>

            @endif

            @include('partials.attachments', ['uploadRoute' => route('outgoing.attach', $record), 'defaultCategory' => $record->status === 'received' ? 'proof_of_receipt' : 'document'])
        </div>
    </div>
</x-layouts.app>
