<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · MEPTDI DTS</title>
    <link rel="icon" type="image/png" href="{{ asset(config('dts.favicon')) }}">
    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-white">
    <div class="grid min-h-screen lg:grid-cols-2">

        {{-- Institutional panel --}}
        <section class="hidden flex-col justify-between bg-navy-900 px-14 py-12 text-white lg:flex">
            <div class="flex items-center gap-3">
                <x-logo unit="pcg" class="size-10" />
                <x-logo unit="mepcom" class="size-10" />
                <div
                    class="ml-2 border-l border-white/15 pl-4 text-[10.5px] leading-relaxed tracking-[0.14em] text-navy-200 uppercase">
                    {{ config('dts.service') }}<br>{{ config('dts.parent_command') }}
                </div>
            </div>

            <div class="max-w-md">
                <x-logo class="size-32" />
                <h1 class="mt-10 text-[30px] leading-[1.15] font-bold">{{ config('dts.office_name') }}</h1>
                <div class="mt-6 h-[3px] w-14 bg-accent-500"></div>
                <p class="mt-6 text-[15px] leading-relaxed text-navy-200">{{ config('dts.system_name') }}</p>
            </div>

            <p class="text-[11px] tracking-wide text-navy-400">© {{ now()->year }} {{ config('dts.office_code') }} ·
                Version {{ config('dts.version') }} · For official use only</p>
        </section>

        {{-- Sign-in --}}
        <section class="flex flex-col items-center justify-center bg-cream px-6 py-12">
            <div class="w-full max-w-[380px]">
                {{-- Shown on small screens instead of the side panel --}}
                <div class="mb-10 text-center lg:hidden">
                    <div class="flex items-center justify-center gap-3">
                        <x-logo unit="pcg" class="size-10" />
                        <x-logo class="size-16" />
                        <x-logo unit="mepcom" class="size-10" />
                    </div>
                    <div class="mt-4 text-sm leading-snug font-bold text-navy-900 uppercase">
                        {{ config('dts.office_name') }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ config('dts.system_name') }}</div>
                </div>

                <div
                    class="rounded-lg bg-white px-8 py-10 shadow-[0_1px_3px_rgba(8,26,56,0.06),0_12px_32px_-12px_rgba(8,26,56,0.18)]">
                    <h2 class="text-2xl font-bold text-navy-900">Welcome</h2>
                    <p class="mt-1.5 text-sm text-slate-500">Sign in with the account issued by the system
                        administrator.</p>

                    @if (session('status'))
                        <p class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                        @csrf
                        <div>
                            <label for="username" class="label">Username</label>
                            <input id="username" name="username" value="{{ old('username') }}"
                                class="input px-3.5 py-2.5" required autofocus autocomplete="username">
                            @error('username')
                                <p class="input-error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="password" class="label">Password</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" class="input py-2.5 pr-16 pl-3.5"
                                    required autocomplete="current-password">
                                <button type="button" id="toggle-password"
                                    class="absolute inset-y-0 right-0 px-3.5 text-xs font-semibold text-navy-500 hover:text-navy-800"
                                    aria-label="Show password">Show</button>
                            </div>
                            <div class="text-right">
                                <a href="{{ route('password.request') }}"
                                    class="text-sm font-semibold text-navy-700 hover:text-navy-900 hover:underline">
                                    Forgot password?
                                </a>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-[13px] text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-navy-300"> Keep me signed in on
                            this computer
                        </label>
                        <button class="btn btn-primary w-full py-3 text-sm">Sign in</button>
                    </form>
                </div>

                <p class="mt-8 text-center text-[11px] leading-relaxed text-slate-400">
                    Authorized personnel only. All activity is recorded in the audit trail.
                    <span class="lg:hidden"><br>Version {{ config('dts.version') }}</span>
                </p>
            </div>
        </section>
    </div>

    <script>
        // Show / hide password
        document.getElementById('toggle-password').addEventListener('click', function() {
            const input = document.getElementById('password');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.textContent = show ? 'Hide' : 'Show';
            this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    </script>
</body>

</html>
