<x-layouts.app title="User Accounts">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl text-navy-900">User accounts</h1>
            <p class="text-sm text-slate-500">All accounts have the same access. Accounts are deactivated, never deleted, so every record stays linked to the person who made it.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" data-modal-open="modal-user" class="btn btn-accent"><x-icon name="plus" /> Add user</a>
    </div>

    <div class="card p-4">
        <table class="table" data-datatable data-order='[[0,"asc"]]' data-search-placeholder="Search name, username, serial no.…" data-empty="No accounts yet.">
            <thead><tr><th>Name</th><th>Username</th><th>Serial no.</th><th>Designation</th><th>Status</th><th>Last sign-in</th><th class="no-sort"></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr data-href="{{ route('admin.users.edit', $u) }}" class="cursor-pointer">
                    <td class="font-medium text-navy-900" data-order="{{ $u->last_name }} {{ $u->first_name }}">{{ $u->displayName() }}</td>
                    <td class="font-mono text-xs">{{ $u->username }}</td>
                    <td class="text-xs">{{ $u->serial_number ?? '—' }}</td>
                    <td class="text-xs">{{ $u->designation ?? '—' }}</td>
                    <td><span class="badge {{ $u->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-xs whitespace-nowrap" data-order="{{ $u->last_login_at?->timestamp ?? 0 }}">{{ $u->last_login_at?->format('d M Y H:i') ?? 'Never' }}</td>
                    <td class="text-right"><a href="{{ route('admin.users.edit', $u) }}" class="text-xs font-semibold text-accent-600 hover:underline">Edit</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Add user pop-up (reopens with the errors if the submission fails) --}}
    @php $userErrors = $errors->getBag('user')->any(); @endphp
    <x-modal id="modal-user" title="Add user" subtitle="All accounts have the same access to the system." :open="$userErrors">
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="p-6">
                @if ($userErrors)
                    <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Please correct the highlighted fields.</div>
                @endif
                @include('admin.users._fields', ['user' => $newUser, 'p' => 'mu-', 'bag' => 'user', 'useOld' => $userErrors])
            </div>
            <div class="flex justify-end gap-2 border-t border-navy-100 bg-navy-50/50 px-6 py-4">
                <button type="button" data-modal-close class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create account</button>
            </div>
        </form>
    </x-modal>
</x-layouts.app>
