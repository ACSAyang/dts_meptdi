{{-- Cells of one audit-trail row (rendered per column by the DataTables endpoint). --}}
@fragment('time')
    <span class="text-xs whitespace-nowrap tabular-nums">{{ $log->created_at->format('d M Y H:i:s') }}</span>
@endfragment
@fragment('user')
    <span class="text-xs">{{ $log->user?->displayName() ?? '—' }}</span>
@endfragment
@fragment('action')
    <span class="badge bg-navy-50 font-mono text-navy-700">{{ $log->action }}</span>
@endfragment
@fragment('reference')
    <span class="ref text-xs whitespace-nowrap">{{ $log->reference_no }}</span>
@endfragment
@fragment('details')
    <div class="text-xs">
        {{ $log->description }}
        @if ($log->reason)<div class="text-amber-800"><strong>Reason:</strong> {{ $log->reason }}</div>@endif
        @if ($log->old_values)
            <details class="mt-1">
                <summary class="cursor-pointer text-accent-600">Changes</summary>
                <table class="mt-1 text-[11px]">
                    @foreach ($log->new_values ?? [] as $field => $new)
                        <tr>
                            <td class="pr-2 font-semibold">{{ str_replace('_', ' ', $field) }}</td>
                            <td class="pr-2 text-red-700 line-through">{{ is_scalar($log->old_values[$field] ?? null) ? $log->old_values[$field] : json_encode($log->old_values[$field] ?? null) }}</td>
                            <td class="text-emerald-700">{{ is_scalar($new) ? $new : json_encode($new) }}</td>
                        </tr>
                    @endforeach
                </table>
            </details>
        @endif
    </div>
@endfragment
@fragment('ip')
    <span class="font-mono text-[11px] text-slate-500">{{ $log->ip_address }}</span>
@endfragment
