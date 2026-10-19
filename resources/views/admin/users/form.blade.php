<x-layouts.app :title="$user->exists ? 'Edit Account' : 'New Account'">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card mx-auto max-w-3xl">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="card-header"><h2 class="card-title">{{ $user->exists ? 'Edit account — '.$user->username : 'New account' }}</h2></div>
        <div class="grid gap-4 p-5 md:grid-cols-3">
            <x-field label="Rank" name="rank" :value="$user->rank" />
            <x-field label="Full name" name="name" :value="$user->name" required class="md:col-span-2" />
            <x-field label="Serial number" name="serial_number" :value="$user->serial_number" />
            <x-field label="Designation" name="designation" :value="$user->designation" class="md:col-span-2" />
            <x-field label="Username" name="username" :value="$user->username" required />
            <x-field label="E-mail (optional)" name="email" type="email" :value="$user->email" />
            <x-field :label="$user->exists ? 'New password (leave blank to keep)' : 'Password'" name="password" type="password" :required="! $user->exists" autocomplete="new-password" />
            <x-field label="Confirm password" name="password_confirmation" type="password" :required="! $user->exists" autocomplete="new-password" />
            <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-navy-300" @checked(old('is_active', $user->is_active))> Account active
            </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-navy-100 px-5 py-4">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary">Save</button>
        </div>
    </form>
</x-layouts.app>
