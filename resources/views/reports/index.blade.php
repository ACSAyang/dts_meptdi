<x-layouts.app title="Reports">
    <h1 class="mb-1 text-xl font-semibold text-navy-900">Reports</h1>
    <p class="mb-6 text-sm text-slate-500">All reports are print-ready (A4 landscape) with the MEPTDI header.</p>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ([
            ['reports.daily', 'Daily Register', 'Incoming and outgoing communications for one day, with the Duty Office Watch on duty. The digital equivalent of the logbook page.', 'report'],
            ['reports.pending', 'Pending-Action Report', 'Every open incoming communication with routing, due date and current status. Overdue items are flagged.', 'clock'],
            ['reports.delivery', 'Delivery Report', 'Outgoing communications for a date range: dispatch, attempts, recipient and verification.', 'truck'],
            ['reports.duty', 'Duty Office Watch Report', 'Duty periods with transactions recorded, turnover and acknowledgment details.', 'shield'],
            ['search', 'Searchable History', 'Search incoming and outgoing records by reference, sender, destination, subject or liaison.', 'search'],
        ] as [$route, $title, $text, $icon])
            <a href="{{ route($route) }}" class="card flex gap-4 p-5 transition hover:border-navy-300 hover:shadow-sm">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-navy-50 text-navy-700"><x-icon :name="$icon" class="size-5" /></div>
                <div>
                    <div class="font-semibold text-navy-900">{{ $title }}</div>
                    <div class="mt-0.5 text-sm text-slate-500">{{ $text }}</div>
                </div>
            </a>
        @endforeach
    </div>
</x-layouts.app>
