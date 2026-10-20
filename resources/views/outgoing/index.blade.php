<x-layouts.app title="Outgoing Communications">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl text-navy-900">Outgoing Communications</h1>
            <p class="text-sm text-slate-500">Dispatch registry tracked through confirmed receipt. Click a row to open it.</p>
        </div>
        <a href="{{ route('outgoing.create') }}" data-modal-open="modal-outgoing" class="btn btn-accent"><x-icon name="plus" /> Register outgoing</a>
    </div>

    <form id="outgoing-filters" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div class="w-full sm:w-56">
            <label for="f-status" class="label">Status</label>
            <select id="f-status" name="status" class="input">
                <option value="">All statuses</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending (open)</option>
                @foreach (config('dts.outgoing_statuses') as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div><label for="f-from" class="label">Registered from</label><input id="f-from" type="date" name="from" value="{{ request('from') }}" class="input"></div>
        <div><label for="f-to" class="label">To</label><input id="f-to" type="date" name="to" value="{{ request('to') }}" class="input"></div>
        <div class="flex gap-2">
            <button class="btn btn-primary">Apply</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
        </div>
    </form>

    <div class="card p-4">
        <table class="table" data-datatable data-source="{{ route('outgoing.index') }}" data-filters="#outgoing-filters"
               data-order='[[1,"desc"]]' data-search-placeholder="Search reference, destination, liaison…" data-empty="No outgoing communications yet.">
            <thead><tr><th>Reference</th><th>Registered</th><th>Destination</th><th>Type / Subject</th><th>Liaison / Dispatch</th><th>Status</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</x-layouts.app>
