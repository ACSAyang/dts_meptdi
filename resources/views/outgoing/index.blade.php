<x-layouts.app title="Outgoing Communications">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-navy-900">Outgoing Communications</h1>
            <p class="text-sm text-slate-500">Dispatch registry tracked through confirmed receipt.</p>
        </div>
        <a href="{{ route('outgoing.create') }}" class="btn btn-primary"><x-icon name="plus" /> Register outgoing</a>
    </div>

    <form class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        <input name="q" value="{{ request('q') }}" placeholder="Reference, destination, liaison, subject…" class="input sm:col-span-2 lg:col-span-1 2xl:col-span-2">
        <select name="status" class="input">
            <option value="">All statuses</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending (open)</option>
            @foreach (config('dts.outgoing_statuses') as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="input" title="Registered from">
        <input type="date" name="to" value="{{ request('to') }}" class="input" title="Registered to">
        <div class="flex gap-2">
            <button class="btn btn-primary flex-1">Filter</button>
            <a href="{{ route('outgoing.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Reference</th><th>Registered</th><th>Destination</th><th>Type / Subject</th><th>Liaison / Dispatch</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($records as $r)
                    <tr class="cursor-pointer" onclick="window.location='{{ route('outgoing.show', $r) }}'">
                        <td class="whitespace-nowrap">
                            <a href="{{ route('outgoing.show', $r) }}" class="ref hover:underline">{{ $r->reference_no }}</a>
                            <div class="mt-1 flex gap-1">
                                @if ($r->sensitivity !== 'unclassified')<x-sensitivity :record="$r" />@endif
                                @if ($r->priority !== 'routine')<span class="badge {{ $r->priority === 'urgent' ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-800' }}">{{ $r->priorityLabel() }}</span>@endif
                            </div>
                        </td>
                        <td class="text-xs whitespace-nowrap">{{ $r->created_at->format('d M Y') }}<br><span class="text-slate-500">{{ $r->created_at->format('H:i') }}H</span></td>
                        <td class="text-sm"><div class="font-medium text-navy-900">{{ $r->destination_office }}</div><div class="text-xs text-slate-500">{{ $r->addressee }}</div></td>
                        <td class="max-w-md"><div class="text-[11px] font-semibold tracking-wide text-navy-500 uppercase">{{ $r->document_type }}</div><div class="text-sm">{{ \Illuminate\Support\Str::limit($r->subject, 110) }}</div></td>
                        <td class="text-xs">{{ $r->liaison_name ?? '—' }}@if ($r->dispatched_at)<div class="text-slate-500">{{ $r->dispatched_at->format('d M H:i') }}H</div>@endif</td>
                        <td class="whitespace-nowrap"><x-status :record="$r" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-slate-400">No outgoing communications found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-navy-50 px-5 py-3">{{ $records->links() }}</div>
    </div>
</x-layouts.app>
