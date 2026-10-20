
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password · MEPTDI DTS</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-cream flex items-center justify-center px-6 py-12">
    <main class="w-full max-w-md rounded-lg bg-white p-8 shadow-lg">
        <h1 class="text-2xl font-bold text-navy-900">
            Forgot Password?
        </h1>

        <p class="mt-2 text-sm text-slate-500">
            Enter your registered email address to receive a password
            reset link.
        </p>

        @if (session('status'))
            <p class="mt-5 rounded bg-green-50 p-3 text-sm text-green-800">
                {{ session('status') }}
            </p>
        @endif

        @if ($errors->any())
            <div class="mt-5 text-sm text-red-600">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}"
              class="mt-6 space-y-5">
            @csrf

            <div>
                <label for="email" class="label">Registered email</label>
                <input id="email" name="email" type="email"
                       value="{{ old('email') }}"
                       class="input px-3.5 py-2.5"
                       autocomplete="email" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary w-full py-3">
                Send Password Reset Link
            </button>
        </form>

        <p class="mt-6 text-center text-sm">
            <a href="{{ route('login') }}"
               class="font-semibold text-navy-700 hover:underline">
                Back to Sign in
            </a>
        </p>
    </main>
</body>
</html>
