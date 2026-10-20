{{-- Cells of one outgoing row in the register (rendered per column by the DataTables endpoint). --}}
@fragment('reference')
    <a href="{{ route('outgoing.show', $r) }}" class="ref whitespace-nowrap hover:underline">{{ $r->reference_no }}</a>
    @if ($r->sensitivity !== 'unclassified' || $r->priority !== 'routine')
        <div class="mt-1 flex flex-wrap gap-1">
            @if ($r->sensitivity !== 'unclassified')<x-sensitivity :record="$r" />@endif
            @if ($r->priority !== 'routine')<span class="badge {{ $r->priority === 'urgent' ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-800' }}">{{ $r->priorityLabel() }}</span>@endif
        </div>
    @endif
@endfragment
@fragment('registered')
    <span class="text-xs whitespace-nowrap">{{ $r->created_at->format('d M Y') }}<br><span class="text-slate-500">{{ $r->created_at->format('H:i') }}H</span></span>
@endfragment
@fragment('destination')
    <div class="font-medium text-navy-900">{{ $r->destination_office }}</div><div class="text-xs text-slate-500">{{ $r->addressee }}</div>
@endfragment
@fragment('subject')
    <div class="text-[11px] font-semibold tracking-wide text-navy-500 uppercase">{{ $r->document_type }}</div>
    <div class="max-w-md text-sm">{{ \Illuminate\Support\Str::limit($r->subject, 110) }}</div>
@endfragment
@fragment('liaison')
    <span class="text-xs">{{ $r->liaison_name ?? '—' }}</span>
    @if ($r->dispatched_at)<div class="text-xs text-slate-500">{{ $r->dispatched_at->format('d M H:i') }}H</div>@endif
@endfragment
@fragment('status')
    <span class="whitespace-nowrap"><x-status :record="$r" /></span>
@endfragment
