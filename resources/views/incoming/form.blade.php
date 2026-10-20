@php $editing = $record->exists; @endphp
<x-layouts.app :title="$editing ? 'Edit '.$record->reference_no : 'Register Incoming Communication'">
    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('incoming.update', $record) : route('incoming.store') }}" class="mx-auto max-w-5xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div>
            <h1 class="text-xl text-navy-900">{{ $editing ? 'Edit incoming record' : 'Register incoming communication' }}</h1>
            <p class="text-sm text-slate-500">
                @if ($editing) Reference <span class="ref">{{ $record->reference_no }}</span> · every change is recorded with its reason.
                @else Reference number to be issued: <span class="ref">{{ $nextReference }}</span> (final number is assigned on saving)
                @endif
            </p>
        </div>

        <div class="card p-6">
            @include('incoming._fields', ['bag' => $editing ? 'default' : 'incoming'])
        </div>

        @if ($editing)
            <div class="card border-amber-200 p-6">
                <x-field label="Reason for the change" name="correction_reason" type="textarea" rows="2" required placeholder="State why the record is being changed" />
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ $editing ? route('incoming.show', $record) : route('incoming.index') }}" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Register' }}</button>
        </div>
    </form>
</x-layouts.app>
