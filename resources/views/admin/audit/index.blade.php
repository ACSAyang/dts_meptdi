<x-layouts.app title="Audit Trail">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl text-navy-900">Audit trail</h1>
            <p class="text-sm text-slate-500">Every sign-in, registration, correction, closure, cancellation, file access and backup. Entries cannot be edited.</p>
        </div>
        <button data-print class="no-print btn btn-secondary"><x-icon name="print" /> Print</button>
    </div>

    <form id="audit-filters" class="no-print card mb-4 flex flex-wrap items-end gap-3 p-4">
        <div class="w-full sm:w-44">
            <label for="a-action" class="label">Module</label>
            <select id="a-action" name="action" class="input">
                <option value="">All modules</option>
                @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ ucfirst($a) }}</option>@endforeach
            </select>
        </div>
        <div class="w-full sm:w-64">
            <label for="a-user" class="label">User</label>
            <select id="a-user" name="user_id" class="input">
                <option value="">All users</option>
                @foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->displayName() }}</option>@endforeach
            </select>
        </div>
        <div><label for="a-from" class="label">From</label><input id="a-from" type="date" name="from" value="{{ request('from') }}" class="input"></div>
        <div><label for="a-to" class="label">To</label><input id="a-to" type="date" name="to" value="{{ request('to') }}" class="input"></div>
        <div class="flex gap-2">
            <button class="btn btn-primary">Apply</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
        </div>
    </form>

    <div class="card p-4">
        <table class="table" data-datatable data-source="{{ route('admin.audit.index') }}" data-filters="#audit-filters"
               data-order='[[0,"desc"]]' data-page-length="50" data-search-placeholder="Search reference, description, reason…" data-empty="No audit entries.">
            <thead><tr><th>Date / time</th><th class="no-sort">User</th><th>Action</th><th>Reference</th><th class="no-sort">Details</th><th>IP</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</x-layouts.app>
