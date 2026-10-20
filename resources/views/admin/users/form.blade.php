<x-layouts.app :title="$user->exists ? 'Edit Account' : 'New Account'">

    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
        class="card mx-auto max-w-3xl">
        @csrf

        @if ($user->exists)
            @method('PUT')
        @endif

        {{-- Header --}}
        <div class="card-header">
            <h2 class="card-title">
                {{ $user->exists ? 'Edit account — ' . $user->username : 'New account' }}
            </h2>
        </div>

        {{-- Account Information --}}
        <div class="grid gap-4 p-5 md:grid-cols-3">

            <x-field label="Rank" name="rank" :value="$user->rank" />

            <x-field label="Full name" name="name" :value="$user->name" required class="md:col-span-2" />

            <x-field label="Serial number" name="serial_number" :value="$user->serial_number" />

            <x-field label="Designation" name="designation" :value="$user->designation" class="md:col-span-2" />

            <x-field label="Username" name="username" :value="$user->username" required />

            <x-field label="E-mail (optional)" name="email" type="email" :value="$user->email" />

            {{-- Password --}}
            <div class="relative">
                <x-field :label="$user->exists ? 'New password (leave blank to keep)' : 'Password'" name="password" type="password" :required="!$user->exists"
                    autocomplete="new-password" />

                <button type="button" onclick="togglePasswordVisibility('password', this)"
                    class="absolute right-3 top-[2.6rem] -translate-y-1/2 z-10 flex items-center justify-center text-slate-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                    aria-label="Show password" title="Show password">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </button>
            </div>

            {{-- Confirm Password --}}
            <div class="relative">
                <x-field label="Confirm password" name="password_confirmation" type="password" :required="!$user->exists"
                    autocomplete="new-password" />

                <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)"
                    class="absolute right-3 top-[2.6rem] -translate-y-1/2 z-10 flex items-center justify-center text-slate-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                    aria-label="Show confirm password" title="Show confirm password">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </button>
            </div>

            {{-- Account Status --}}
            <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-700">
                <input type="hidden" name="is_active" value="0">

                <input type="checkbox" name="is_active" value="1" class="rounded border-navy-300"
                    @checked(old('is_active', $user->is_active))>

                Account active
            </label>

        </div>

        {{-- Footer Actions --}}
        <div class="flex justify-end gap-2 border-t border-navy-100 px-5 py-4">

            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                Save
            </button>

        </div>

    </form>

    {{-- Show / Hide Password Script --}}
    <script>
        function togglePasswordVisibility(fieldName, button) {
            const input = document.querySelector(
                'input[name="' + fieldName + '"]'
            );

            if (!input) {
                return;
            }

            const showPassword = input.type === 'password';

            input.type = showPassword ? 'text' : 'password';

            button.setAttribute(
                'aria-label',
                showPassword ? 'Hide password' : 'Show password'
            );

            button.setAttribute(
                'title',
                showPassword ? 'Hide password' : 'Show password'
            );

            // Change the eye icon when the password is visible.
            button.innerHTML = showPassword ?
                `<svg xmlns="http://www.w3.org/2000/svg"
                       class="h-5 w-5"
                       viewBox="0 0 24 24"
                       fill="none"
                       stroke="currentColor"
                       stroke-width="2"
                       stroke-linecap="round"
                       stroke-linejoin="round">
                       <path d="M3 3l18 18"/>
                       <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                       <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a16.6 16.6 0 0 1-4 4.9"/>
                       <path d="M6.6 6.6C3.6 8.5 2 12 2 12s3.6 7 10 7c1.1 0 2.1-.2 3-.5"/>
                   </svg>` :
                `<svg xmlns="http://www.w3.org/2000/svg"
                       class="h-5 w-5"
                       viewBox="0 0 24 24"
                       fill="none"
                       stroke="currentColor"
                       stroke-width="2"
                       stroke-linecap="round"
                       stroke-linejoin="round">
                       <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                       <circle cx="12" cy="12" r="3"/>
                   </svg>`;
        }
    </script>

</x-layouts.app>
