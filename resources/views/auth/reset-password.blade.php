<x-layouts.guest title="Reset Password">
    <h1 class="text-2xl font-bold text-navy-900">Set a new password</h1>
    <p class="mt-1.5 text-sm text-slate-500">Choose a new password for your account.</p>

    <form method="POST" action="{{ route('password.update_user') }}" class="mt-7 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-field label="Registered e-mail" name="email" type="email" :value="old('email', $request->email)" required readonly />
        <x-field label="New password" name="password" type="password" required autocomplete="new-password" hint="At least 8 characters." />
        <x-field label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />

        <button type="submit" class="btn btn-primary w-full py-3">Reset password</button>
    </form>

    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-navy-700 hover:underline">Back to sign in</a></p>
</x-layouts.guest>
