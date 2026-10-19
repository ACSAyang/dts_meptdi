<x-layouts.app title="Incoming Communications">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-navy-900">Incoming Communications</h1>
            <p class="text-sm text-slate-500">Registry of communications received by MEPTDI.</p>
        </div>
        <a href="{{ route('incoming.create') }}" class="btn btn-primary"><x-icon name="plus" /> Register incoming</a>
    </div>

    <form class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        <input name="q" value="{{ request('q') }}" placeholder="Reference, sender, subject…" class="input sm:col-span-2 lg:col-span-1 2xl:col-span-2">
        <select name="status" class="input">
            <option value="">All statuses</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending (open)</option>
            @foreach (config('dts.incoming_statuses') as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="input" title="Received from">
        <input type="date" name="to" value="{{ request('to') }}" class="input" title="Received to">
        <div class="flex gap-2">
            <button class="btn btn-primary flex-1">Filter</button>
            <a href="{{ route('incoming.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Reference</th><th>Received</th><th>Sender</th><th>Type / Subject</th><th>Routed to</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($records as $r)
                    <tr class="cursor-pointer" onclick="window.location='{{ route('incoming.show', $r) }}'">
                        <td class="whitespace-nowrap">
                            <a href="{{ route('incoming.show', $r) }}" class="ref hover:underline">{{ $r->reference_no }}</a>
                            <div class="mt-1 flex gap-1">
                                @if ($r->sensitivity !== 'unclassified')<x-sensitivity :record="$r" />@endif
                                @if ($r->priority !== 'routine')<span class="badge {{ $r->priority === 'urgent' ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-800' }}">{{ $r->priorityLabel() }}</span>@endif
                            </div>
                        </td>
                        <td class="text-xs whitespace-nowrap">{{ $r->received_at->format('d M Y') }}<br><span class="text-slate-500">{{ $r->received_at->format('H:i') }}H</span></td>
                        <td class="text-sm"><div class="font-medium text-navy-900">{{ $r->sender_name }}</div><div class="text-xs text-slate-500">{{ $r->sender_office }}</div></td>
                        <td class="max-w-md"><div class="text-[11px] font-semibold tracking-wide text-navy-500 uppercase">{{ $r->document_type }}</div><div class="text-sm">{{ \Illuminate\Support\Str::limit($r->subject, 110) }}</div></td>
                        <td class="text-xs">{{ $r->routed_to ?? '—' }}@if ($r->action_due_at)<div class="text-slate-500">Due {{ $r->action_due_at->format('d M H:i') }}</div>@endif</td>
                        <td class="whitespace-nowrap"><x-status :record="$r" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-slate-400">No incoming communications found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-navy-50 px-5 py-3">{{ $records->links() }}</div>
    </div>
</x-layouts.app>
