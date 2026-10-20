<x-layouts.app title="Incoming Communications">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl text-navy-900">Incoming Communications</h1>
            <p class="text-sm text-slate-500">Registry of communications received by MEPTDI. Click a row to open it.</p>
        </div>
        <a href="{{ route('incoming.create') }}" data-modal-open="modal-incoming" class="btn btn-accent"><x-icon name="plus" /> Register incoming</a>
    </div>

    <form id="incoming-filters" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div class="w-full sm:w-48">
            <label for="f-status" class="label">Status</label>
            <select id="f-status" name="status" class="input">
                <option value="">All statuses</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending (open)</option>
                @foreach (config('dts.incoming_statuses') as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="f-from" class="label">Received from</label><input id="f-from" type="date" name="from" value="{{ request('from') }}" class="input"></div>
        <div><label for="f-to" class="label">To</label><input id="f-to" type="date" name="to" value="{{ request('to') }}" class="input"></div>
        <input type="hidden" name="overdue" value="{{ request('overdue') }}">
        <div class="flex gap-2">
            <button class="btn btn-primary">Apply</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
        </div>
    </form>

    <div class="card p-4">
        <table class="table" data-datatable data-source="{{ route('incoming.index') }}" data-filters="#incoming-filters"
               data-order='[[1,"desc"]]' data-search-placeholder="Search reference, sender, subject…" data-empty="No incoming communications yet.">
            <thead><tr><th>Reference</th><th>Received</th><th>Sender</th><th>Type / Subject</th><th>Routed to</th><th>Status</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</x-layouts.app>
