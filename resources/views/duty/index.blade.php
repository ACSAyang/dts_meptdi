<x-layouts.app title="Duty Office Watch">
    @php $user = auth()->user(); @endphp

    <div class="mb-5">
        <h1 class="text-xl text-navy-900">Duty Office Watch</h1>
        <p class="text-sm text-slate-500">One watch is on duty at a time. Start your duty when you take the watch, and turn over when you hand it to the next watch.</p>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @if ($current)
                {{-- Someone is on duty --}}
                <div class="card">
                    <div class="flex flex-wrap items-center gap-5 px-6 py-5">
                        <div class="flex size-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                            <x-icon name="shield" class="size-6" />
                        </div>
                        <div class="flex-1">
                            <div class="text-[11px] font-semibold tracking-wider text-emerald-700 uppercase">On duty now</div>
                            <div class="text-lg font-bold text-navy-900">{{ $current->user->displayName() }}{{ $current->user_id === $user->id ? ' (you)' : '' }}</div>
                            <div class="text-xs text-slate-500">Since {{ $current->started_at->format('d M Y · H:i') }}H · until {{ $current->scheduled_end->format('d M Y · H:i') }}H</div>
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('duty.show', $current) }}" class="btn btn-secondary">Duty log</a>
                            <a href="{{ route('duty.turnover', $current) }}" class="btn btn-primary">End duty &amp; turn over</a>
                        </div>
                    </div>
                </div>
            @else
                {{-- Nobody on duty: start duty --}}
                <form method="POST" action="{{ route('duty.start') }}" class="card">
                    @csrf
                    <div class="card-header"><h2 class="card-title">Start duty</h2></div>
                    <div class="space-y-5 p-6">
                        <p class="text-sm text-slate-600">No one is on duty. You will be recorded as the Duty Office Watch on duty from now: <strong class="text-navy-900">{{ $user->displayName() }}</strong>.</p>

                        @if ($awaitingTurnover)
                            <div class="rounded-md border border-accent-400/50 bg-accent-500/5 p-4">
                                <div class="text-sm font-semibold text-navy-900">Turnover from {{ $awaitingTurnover->fromUser->displayName() }}</div>
                                <div class="text-xs text-slate-500">{{ $awaitingTurnover->turned_over_at->format('d M Y · H:i') }}H</div>
                                <div class="mt-3 rounded bg-white px-4 py-3 text-sm whitespace-pre-line text-slate-700">{{ $awaitingTurnover->notes }}</div>
                                <div class="mt-3">@include('duty.partials.snapshot', ['snapshot' => $awaitingTurnover->pending_snapshot])</div>
                                <label class="mt-4 flex items-start gap-2 text-sm font-medium text-navy-900">
                                    <input type="checkbox" name="acknowledge" value="1" class="mt-0.5 rounded border-navy-300" @checked(old('acknowledge'))>
                                    I have received the turnover notes and pending items.
                                </label>
                                @error('acknowledge')<p class="input-error">{{ $message }}</p>@enderror
                                <textarea name="acknowledgment_remarks" rows="2" class="input mt-3" placeholder="Remarks (optional)">{{ old('acknowledgment_remarks') }}</textarea>
                            </div>
                        @endif

                        <div class="max-w-xs">
                            <x-field label="Duty ends" name="scheduled_end" type="datetime-local" :value="now()->addDay()->startOfHour()" required />
                        </div>
                        <button class="btn btn-accent px-6 py-2.5">Start my duty</button>
                    </div>
                </form>
            @endif

            {{-- History --}}
            <div class="card">
                <div class="card-header"><h2 class="card-title">Duty history</h2></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Watch</th><th>Started</th><th>Ended</th><th>Turned over to</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($history as $period)
                            <tr>
                                <td class="font-medium text-navy-900">{{ $period->user->displayName() }}
                                    @if ($period->status === 'active')<span class="badge ml-1 bg-emerald-50 text-emerald-700">On duty</span>@endif
                                </td>
                                <td class="text-xs whitespace-nowrap">{{ $period->started_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="text-xs whitespace-nowrap">{{ $period->ended_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="text-xs">
                                    @if ($period->turnoverGiven)
                                        {{ $period->turnoverGiven->toUser?->displayName() ?? 'Waiting for acknowledgment' }}
                                    @else — @endif
                                </td>
                                <td class="text-right"><a href="{{ route('duty.show', $period) }}" class="text-xs font-semibold text-accent-600 hover:underline">View log</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-slate-400">No duty periods yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($history->hasPages())<div class="border-t border-navy-50 px-5 py-3">{{ $history->links() }}</div>@endif
            </div>
        </div>

        {{-- How it works --}}
        <div class="card h-fit p-5 text-sm text-slate-600">
            <h2 class="card-title mb-3">How it works</h2>
            <ol class="list-decimal space-y-2 pl-4">
                <li><strong class="text-navy-900">Start duty</strong> when you take the watch. If the previous watch left a turnover, read it and acknowledge it.</li>
                <li>Record incoming and outgoing communications. Each entry is linked to the watch on duty.</li>
                <li><strong class="text-navy-900">End duty &amp; turn over</strong> when you leave. Write your notes; pending items are listed automatically for the next watch.</li>
            </ol>
        </div>
    </div>
</x-layouts.app>
