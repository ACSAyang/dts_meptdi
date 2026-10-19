<x-layouts.app title="Search Records">
    <h1 class="mb-4 text-xl font-semibold text-navy-900">Search records</h1>

    <form class="card mb-6 flex gap-2 p-4">
        <input name="q" value="{{ $q }}" class="input text-base" placeholder="Reference no., sender, destination, subject, liaison, recipient…" autofocus>
        <button class="btn btn-primary px-6"><x-icon name="search" /> Search</button>
    </form>

    @if (mb_strlen($q) >= 2)
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ([['Incoming', $incoming, 'incoming.show'], ['Outgoing', $outgoing, 'outgoing.show']] as [$label, $results, $route])
                <div class="card">
                    <div class="card-header"><h2 class="card-title">{{ $label }} ({{ $results->count() }})</h2></div>
                    <ul class="divide-y divide-navy-50">
                        @forelse ($results as $r)
                            <li>
                                <a href="{{ route($route, $r) }}" class="block px-5 py-3 hover:bg-navy-50/60">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="ref">{{ $r->reference_no }}</span>
                                        <x-status :record="$r" />
                                    </div>
                                    <div class="mt-0.5 text-sm text-slate-700">{{ \Illuminate\Support\Str::limit($r->subject, 120) }}</div>
                                    <div class="text-xs text-slate-500">
                                        @if ($r instanceof \App\Models\IncomingCommunication)
                                            From {{ $r->sender_name }} · {{ $r->received_at->format('d M Y') }}
                                        @else
                                            To {{ $r->destination_office }} · {{ $r->created_at->format('d M Y') }}
                                        @endif
                                    </div>
                                </a>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-slate-400">No matches.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    @elseif ($q !== '')
        <p class="text-sm text-slate-500">Enter at least 2 characters.</p>
    @endif
</x-layouts.app>
