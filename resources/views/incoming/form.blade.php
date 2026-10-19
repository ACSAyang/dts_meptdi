@php $editing = $record->exists; @endphp
<x-layouts.app :title="$editing ? 'Correct '.$record->reference_no : 'Register Incoming Communication'">
    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('incoming.update', $record) : route('incoming.store') }}" class="mx-auto max-w-5xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-navy-900">{{ $editing ? 'Correct incoming record' : 'Register incoming communication' }}</h1>
                <p class="text-sm text-slate-500">
                    @if ($editing) Reference <span class="ref">{{ $record->reference_no }}</span> · every correction is recorded with its reason.
                    @else Reference number to be issued: <span class="ref">{{ $nextReference }}</span> (final number is assigned on saving)
                    @endif
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Receipt details</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                <x-field label="Date / time received" name="received_at" type="datetime-local" :value="$record->received_at" required />
                <x-field label="Mode of receipt" name="mode_of_receipt" :options="config('dts.receipt_modes')" :value="$record->mode_of_receipt" />
                <x-field label="Enclosures / pages" name="enclosures" :value="$record->enclosures" placeholder="e.g. 3 pages, 1 annex" />
                <x-field label="Sender (name / position)" name="sender_name" :value="$record->sender_name" required class="md:col-span-2" />
                <x-field label="Sender office / unit" name="sender_office" :value="$record->sender_office" />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Document</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                <x-field label="Document type" name="document_type" :options="config('dts.document_types')" :value="$record->document_type" required />
                <x-field label="Sender's reference no." name="sender_reference" :value="$record->sender_reference" />
                <x-field label="Document date" name="document_date" type="date" :value="$record->document_date" />
                <x-field label="Subject" name="subject" type="textarea" rows="2" :value="$record->subject" required class="md:col-span-3" />
                <x-field label="Sensitivity" name="sensitivity" :options="config('dts.sensitivities')" :value="$record->sensitivity" required
                         hint="Handling label. Do not encode Secret or higher." />
                <x-field label="Priority" name="priority" :options="config('dts.priorities')" :value="$record->priority" required />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Handling</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                <x-field label="Handling remarks" name="handling_remarks" type="textarea" rows="2" :value="$record->handling_remarks" class="md:col-span-3" />
                <div class="md:col-span-3">
                    <label class="flex items-center gap-2 text-sm font-medium text-navy-900">
                        <input type="checkbox" name="requires_action" value="1" data-toggle="#action-fields" class="rounded border-navy-300" @checked(old('requires_action', $record->requires_action))>
                        This communication requires action / compliance
                    </label>
                </div>
                <div id="action-fields" class="grid gap-4 md:col-span-3 md:grid-cols-3">
                    <x-field label="Action required" name="action_required" type="textarea" rows="2" :value="$record->action_required" class="md:col-span-2" />
                    <x-field label="Action due" name="action_due_at" type="datetime-local" :value="$record->action_due_at" />
                </div>
            </div>
        </div>

        @if ($editing)
            <div class="card border-amber-200">
                <div class="card-header bg-amber-50/60"><h2 class="card-title text-amber-900">Reason for correction</h2></div>
                <div class="p-5">
                    <x-field label="Reason" name="correction_reason" type="textarea" rows="2" required placeholder="State why the record is being corrected" />
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header"><h2 class="card-title">Attachments (optional)</h2></div>
                <div class="p-5">
                    <input type="file" name="attachments[]" multiple class="input file:mr-3 file:rounded file:border-0 file:bg-navy-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-navy-800">
                    <p class="hint">Scanned copy of the document. PDF, images, Word or Excel; up to 20 MB each.</p>
                    @error('attachments.*')<p class="input-error">{{ $message }}</p>@enderror
                </div>
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ $editing ? route('incoming.show', $record) : route('incoming.index') }}" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save correction' : 'Register' }}</button>
        </div>
    </form>
</x-layouts.app>
