<x-layouts.app title="User Accounts">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-navy-900">User accounts</h1>
            <p class="text-sm text-slate-500">All accounts have the same access. Accounts are deactivated, never deleted, so every record stays linked to the person who made it.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><x-icon name="plus" /> New account</a>
    </div>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Name</th><th>Username</th><th>Serial no.</th><th>Designation</th><th>Status</th><th>Last sign-in</th><th></th></tr></thead>
                <tbody>
                @foreach ($users as $u)
                    <tr>
                        <td class="font-medium text-navy-900">{{ $u->displayName() }}</td>
                        <td class="font-mono text-xs">{{ $u->username }}</td>
                        <td class="text-xs">{{ $u->serial_number ?? '—' }}</td>
                        <td class="text-xs">{{ $u->designation ?? '—' }}</td>
                        <td><span class="badge {{ $u->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">{{ $u->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-xs whitespace-nowrap">{{ $u->last_login_at?->format('d M Y H:i') ?? 'Never' }}</td>
                        <td class="text-right"><a href="{{ route('admin.users.edit', $u) }}" class="text-xs font-medium text-accent-600 hover:underline">Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
