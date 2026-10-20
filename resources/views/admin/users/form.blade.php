<x-layouts.app :title="$user->exists ? 'Edit Account' : 'New Account'">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="mx-auto max-w-3xl space-y-6">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div>
            <h1 class="text-xl text-navy-900">{{ $user->exists ? 'Edit account' : 'New account' }}</h1>
            @if ($user->exists)<p class="text-sm text-slate-500">{{ $user->displayName() }} · {{ $user->username }}</p>@endif
        </div>

        <div class="card p-6">
            @include('admin.users._fields', ['bag' => $user->exists ? 'default' : 'user'])
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</x-layouts.app>
