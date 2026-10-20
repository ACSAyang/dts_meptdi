{{-- Cells of one incoming row in the register (rendered per column by the DataTables endpoint). --}}
@fragment('reference')
    <a href="{{ route('incoming.show', $r) }}" class="ref whitespace-nowrap hover:underline">{{ $r->reference_no }}</a>
    @if ($r->sensitivity !== 'unclassified' || $r->priority !== 'routine')
        <div class="mt-1 flex flex-wrap gap-1">
            @if ($r->sensitivity !== 'unclassified')<x-sensitivity :record="$r" />@endif
            @if ($r->priority !== 'routine')<span class="badge {{ $r->priority === 'urgent' ? 'bg-red-600 text-white' : 'bg-amber-100 text-amber-800' }}">{{ $r->priorityLabel() }}</span>@endif
        </div>
    @endif
@endfragment
@fragment('received')
    <span class="text-xs whitespace-nowrap">{{ $r->received_at->format('d M Y') }}<br><span class="text-slate-500">{{ $r->received_at->format('H:i') }}H</span></span>
@endfragment
@fragment('sender')
    <div class="font-medium text-navy-900">{{ $r->sender_name }}</div><div class="text-xs text-slate-500">{{ $r->sender_office }}</div>
@endfragment
@fragment('subject')
    <div class="text-[11px] font-semibold tracking-wide text-navy-500 uppercase">{{ $r->document_type }}</div>
    <div class="max-w-md text-sm">{{ \Illuminate\Support\Str::limit($r->subject, 110) }}</div>
@endfragment
@fragment('routed')
    <span class="text-xs">{{ $r->routed_to ?? '—' }}</span>
    @if ($r->action_due_at)<div class="text-xs {{ $r->isOverdue() ? 'font-semibold text-red-600' : 'text-slate-500' }}">Due {{ $r->action_due_at->format('d M H:i') }}</div>@endif
@endfragment
@fragment('status')
    <span class="whitespace-nowrap"><x-status :record="$r" /></span>
@endfragment
