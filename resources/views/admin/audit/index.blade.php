<x-layouts.app title="Audit Trail">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-navy-900">Audit trail</h1>
            <p class="text-sm text-slate-500">Every sign-in, registration, correction, closure, cancellation, file access and backup. Entries cannot be edited.</p>
        </div>
        <button data-print class="no-print btn btn-secondary"><x-icon name="print" /> Print</button>
    </div>

    <form class="no-print card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
        <input name="q" value="{{ request('q') }}" placeholder="Reference, description, reason…" class="input sm:col-span-2 lg:col-span-1 2xl:col-span-2">
        <select name="action" class="input">
            <option value="">All modules</option>
            @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ ucfirst($a) }}</option>@endforeach
        </select>
        <select name="user_id" class="input">
            <option value="">All users</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->displayName() }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="input">
        <div class="flex gap-2">
            <input type="date" name="to" value="{{ request('to') }}" class="input">
            <button class="btn btn-primary">Filter</button>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Date / time</th><th>User</th><th>Action</th><th>Reference</th><th>Details</th><th>IP</th></tr></thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="text-xs whitespace-nowrap tabular-nums">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                        <td class="text-xs">{{ $log->user?->displayName() ?? '—' }}</td>
                        <td><span class="badge bg-navy-50 font-mono text-navy-700">{{ $log->action }}</span></td>
                        <td class="ref text-xs whitespace-nowrap">{{ $log->reference_no }}</td>
                        <td class="text-xs">
                            {{ $log->description }}
                            @if ($log->reason)<div class="text-amber-800"><strong>Reason:</strong> {{ $log->reason }}</div>@endif
                            @if ($log->old_values)
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-accent-600">Changes</summary>
                                    <table class="mt-1 text-[11px]">
                                        @foreach ($log->new_values ?? [] as $field => $new)
                                            <tr><td class="pr-2 font-semibold">{{ str_replace('_', ' ', $field) }}</td><td class="pr-2 text-red-700 line-through">{{ is_scalar($log->old_values[$field] ?? null) ? $log->old_values[$field] : json_encode($log->old_values[$field] ?? null) }}</td><td class="text-emerald-700">{{ is_scalar($new) ? $new : json_encode($new) }}</td></tr>
                                        @endforeach
                                    </table>
                                </details>
                            @endif
                        </td>
                        <td class="font-mono text-[11px] text-slate-500">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-slate-400">No entries.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-navy-50 px-5 py-3">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
