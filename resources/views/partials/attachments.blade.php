{{-- $record, $uploadRoute --}}
<div class="card">
    <div class="card-header"><h2 class="card-title">Attachments ({{ $record->attachments->count() }})</h2></div>
    <ul class="divide-y divide-navy-50">
        @forelse ($record->attachments as $file)
            <li class="flex items-center gap-3 px-5 py-3">
                <x-icon name="clip" class="size-4 shrink-0 text-navy-400" />
                <div class="min-w-0 flex-1">
                    <a href="{{ route('attachments.show', $file) }}" target="_blank" class="block truncate text-sm font-medium text-accent-600 hover:underline">{{ $file->original_name }}</a>
                    <div class="text-[11px] text-slate-500">{{ $file->categoryLabel() }} · {{ $file->humanSize() }} · {{ $file->uploader->displayName() }} · {{ $file->created_at->format('d M Y H:i') }}</div>
                    @if ($file->description)<div class="text-xs text-slate-600">{{ $file->description }}</div>@endif
                </div>
                <a href="{{ route('attachments.show', [$file, 'download' => 1]) }}" class="no-print text-xs text-navy-600 hover:underline">Download</a>
            </li>
        @empty
            <li class="px-5 py-4 text-sm text-slate-400">No attachments.</li>
        @endforelse
    </ul>
    @if (\App\Models\DutyPeriod::current())
    <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data" class="no-print space-y-2 border-t border-navy-100 bg-navy-50/40 px-5 py-4">
        @csrf
        <div class="grid gap-2 sm:grid-cols-2">
            <select name="category" class="input py-1.5 text-xs">
                @foreach (config('dts.attachment_categories') as $key => $label)
                    <option value="{{ $key }}" @selected(($defaultCategory ?? 'document') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input name="description" class="input py-1.5 text-xs" placeholder="Description (optional)">
        </div>
        <div class="flex gap-2">
            <input type="file" name="attachments[]" multiple required class="input py-1 text-xs file:mr-2 file:rounded file:border-0 file:bg-navy-100 file:px-2 file:py-0.5 file:text-xs">
            <button class="btn btn-secondary btn-sm">Upload</button>
        </div>
    </form>
    @endif
</div>
