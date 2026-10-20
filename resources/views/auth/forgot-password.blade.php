<x-layouts.guest title="Forgot Password">
    <h1 class="text-2xl font-bold text-navy-900">Forgot password?</h1>
    <p class="mt-1.5 text-sm text-slate-500">Enter the e-mail address registered to your account and we will send you a link to reset your password.</p>

    @if (session('status'))
        <p class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
        @csrf
        <x-field label="Registered e-mail" name="email" type="email" required autofocus autocomplete="email" />
        <button type="submit" class="btn btn-primary w-full py-3">Send password reset link</button>
    </form>

    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-navy-700 hover:underline">Back to sign in</a></p>
</x-layouts.guest>
