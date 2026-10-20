@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}MEPTDI DTS</title>
    <link rel="icon" type="image/png" href="{{ asset(config('dts.favicon')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-cream">
<div class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="flex items-center justify-center gap-3">
                <x-logo unit="pcg" class="size-11" />
                <x-logo class="size-16" />
                <x-logo unit="mepcom" class="size-11" />
            </div>
            <div class="mt-4 text-sm leading-snug font-bold text-navy-900 uppercase">{{ config('dts.office_name') }}</div>
            <div class="mt-1 text-xs text-slate-500">{{ config('dts.system_name') }}</div>
        </div>

        <div class="rounded-lg bg-white px-8 py-9 shadow-[0_1px_3px_rgba(8,26,56,0.06),0_12px_32px_-12px_rgba(8,26,56,0.18)]">
            {{ $slot }}
        </div>

        <p class="mt-8 text-center text-[11px] text-slate-400">Version {{ config('dts.version') }} · For official use only</p>
    </div>
</div>
</body>
</html>
