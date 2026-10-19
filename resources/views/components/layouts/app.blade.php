<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}MEPTDI DTS</title>
    <link rel="icon" type="image/png" href="{{ asset(config('dts.favicon')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
@php
    $user = auth()->user();
    $onDuty = \App\Models\DutyPeriod::current();
    $nav = [
        ['dashboard', 'Dashboard', 'dashboard', 'dashboard'],
        ['incoming.index', 'Incoming', 'inbox', 'incoming.*'],
        ['outgoing.index', 'Outgoing', 'send', 'outgoing.*'],
        ['duty.index', 'Duty Office Watch', 'shield', 'duty.*'],
        ['reports.index', 'Reports', 'report', 'reports.*'],
        ['search', 'Search Records', 'search', 'search'],
    ];
    $adminNav = [
        ['admin.users.index', 'User Accounts', 'users', 'admin.users.*'],
        ['admin.audit.index', 'Audit Trail', 'audit', 'admin.audit.*'],
        ['admin.backups.index', 'Backup & Restore', 'backup', 'admin.backups.*'],
    ];
@endphp

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside id="sidebar" class="no-print fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-navy-900 text-white transition-transform lg:translate-x-0">
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-5">
            <x-logo class="size-12 shrink-0" />
            <div class="leading-tight">
                <div class="text-[15px] font-bold tracking-[0.15em]">MEPTDI</div>
                <div class="text-[11px] text-navy-200">Document Tracking System</div>
            </div>
        </div>

        <nav class="nav-scroll flex-1 space-y-0.5 overflow-y-auto py-4">
            @foreach ($nav as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                    <x-icon :name="$icon" class="size-[18px]" /> {{ $label }}
                </a>
            @endforeach

            <div class="px-6 pt-5 pb-1.5 text-[10px] font-semibold tracking-[0.15em] text-navy-300 uppercase">System</div>
            @foreach ($adminNav as [$route, $label, $icon, $pattern])
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                    <x-icon :name="$icon" class="size-[18px]" /> {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="border-t border-white/10 px-5 py-4">
            <div class="text-[13px] font-semibold">{{ $user->displayName() }}</div>
            <div class="text-[11px] text-navy-300">{{ $onDuty?->user_id === $user->id ? 'On duty' : ($user->designation ?: 'Duty Office Watch') }}</div>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('password.edit') }}" class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs text-navy-200 hover:bg-white/5 hover:text-white"><x-icon name="key" class="size-3.5" /> Password</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs text-navy-200 hover:bg-white/5 hover:text-white"><x-icon name="logout" class="size-3.5" /> Sign out</button>
                </form>
            </div>
        </div>
    </aside>
    <div id="sidebar-backdrop" class="no-print fixed inset-0 z-30 hidden bg-navy-950/40 lg:hidden"></div>

    {{-- Main --}}
    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="no-print sticky top-0 z-20 bg-white">
            <div class="flex h-14 items-center gap-3 px-4 lg:px-8">
                <button id="sidebar-toggle" class="-ml-1 rounded p-1.5 text-navy-800 hover:bg-navy-50 lg:hidden" aria-label="Open menu"><x-icon name="menu" class="size-5" /></button>
                <div class="min-w-0 flex-1 truncate text-[11px] font-semibold tracking-[0.1em] text-navy-600 uppercase">PCG · MEPCOM · MEPTDI</div>
                <a href="{{ route('duty.index') }}" class="hidden items-center gap-2 rounded-full border px-3 py-1 text-xs sm:inline-flex {{ $onDuty ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-accent-400/60 bg-accent-500/10 text-accent-700' }}">
                    <span class="size-1.5 rounded-full {{ $onDuty ? 'bg-emerald-500' : 'bg-accent-500' }}"></span>
                    @if ($onDuty)
                        DOW: <strong class="font-semibold">{{ $onDuty->user->displayName() }}</strong>
                    @else
                        No Duty Office Watch on duty
                    @endif
                </a>
                <div class="hidden text-xs text-slate-500 tabular-nums md:block" data-clock>{{ now()->format('D, d M Y · H:i') }}H</div>
            </div>
            <div class="pcg-rule"></div>
        </header>

        <main class="flex-1 px-4 py-6 lg:px-8">
            <div class="print-only mb-4"><x-letterhead /></div>

            @foreach (['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'error' => 'border-red-200 bg-red-50 text-red-800', 'warning' => 'border-amber-200 bg-amber-50 text-amber-900'] as $key => $classes)
                @if (session($key))
                    <div class="no-print mb-5 rounded-md border px-4 py-3 text-sm {{ $classes }}">{{ session($key) }}</div>
                @endif
            @endforeach
            @if ($errors->any() && ! ($hideErrorSummary ?? false))
                <div class="no-print mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    Please correct the highlighted fields.
                    <ul class="mt-1 list-disc pl-5 text-xs">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            {{ $slot }}
        </main>

        <footer class="no-print">
            <div class="mx-4 flex flex-wrap items-center justify-between gap-2 border-t border-navy-100 py-4 text-[11px] text-slate-500 lg:mx-8">
                <span>{{ config('dts.office_name') }}</span>
                <span class="font-semibold tracking-wider uppercase">For official use only</span>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
