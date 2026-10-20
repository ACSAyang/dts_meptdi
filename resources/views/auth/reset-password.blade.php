<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password · MEPTDI DTS</title>
    @vite(['resources/css/app.css'])
</head>

<body class="min-h-screen bg-cream flex items-center justify-center px-6 py-12">
    <main class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">
        <h1 class="text-2xl font-bold text-navy-900">
            Set New Password
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            Choose a new password for your account.
        </p>

        @if ($errors->any())
            <div class="mt-5 text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
                    class="input px-3.5 py-2.5" autocomplete="email" readonly>
            </div>

            <div>
                <label for="password" class="label">New password</label>
                <input id="password" name="password" type="password" class="input px-3.5 py-2.5"
                    autocomplete="new-password" required>
            </div>

            <div>
                <label for="password_confirmation" class="label">
                    Confirm new password
                </label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                    class="input px-3.5 py-2.5" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn-primary w-full py-3">
                Reset Password
            </button>
        </form>
    </main>
</body>

</html>
