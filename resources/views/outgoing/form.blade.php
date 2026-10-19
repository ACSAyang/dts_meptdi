@php $editing = $record->exists; @endphp
<x-layouts.app :title="$editing ? 'Correct '.$record->reference_no : 'Register Outgoing Communication'">
    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('outgoing.update', $record) : route('outgoing.store') }}" class="mx-auto max-w-5xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div>
            <h1 class="text-xl font-semibold text-navy-900">{{ $editing ? 'Correct outgoing record' : 'Register outgoing communication' }}</h1>
            <p class="text-sm text-slate-500">
                @if ($editing) Reference <span class="ref">{{ $record->reference_no }}</span> · every correction is recorded with its reason.
                @else Reference number to be issued: <span class="ref">{{ $nextReference }}</span> (final number is assigned on saving)
                @endif
            </p>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Document</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                <x-field label="Document type" name="document_type" :options="config('dts.document_types')" :value="$record->document_type" required />
                <x-field label="Document date" name="document_date" type="date" :value="$record->document_date" />
                <x-field label="Enclosures / pages" name="enclosures" :value="$record->enclosures" />
                <x-field label="Subject" name="subject" type="textarea" rows="2" :value="$record->subject" required class="md:col-span-3" />
                <x-field label="Originating office / section" name="originating_office" :value="$record->originating_office" />
                <x-field label="Signatory" name="signatory" :value="$record->signatory" />
                <div></div>
                <x-field label="Sensitivity" name="sensitivity" :options="config('dts.sensitivities')" :value="$record->sensitivity" required
                         hint="Handling label. Do not encode Secret or higher." />
                <x-field label="Priority" name="priority" :options="config('dts.priorities')" :value="$record->priority" required />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Destination &amp; dispatch</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                <x-field label="Destination office / unit" name="destination_office" :value="$record->destination_office" required />
                <x-field label="Addressee" name="addressee" :value="$record->addressee" />
                <x-field label="Mode of dispatch" name="mode_of_dispatch" :options="config('dts.dispatch_modes')" :value="$record->mode_of_dispatch" required />
                <x-field label="Destination address" name="destination_address" type="textarea" rows="2" :value="$record->destination_address" class="md:col-span-2" />
                <x-field label="Assigned liaison" name="liaison_name" :value="$record->liaison_name" hint="Required before dispatch when hand-carried by liaison." />
                <x-field label="Remarks" name="remarks" type="textarea" rows="2" :value="$record->remarks" class="md:col-span-3" />
            </div>
        </div>

        @if ($editing)
            <div class="card border-amber-200">
                <div class="card-header bg-amber-50/60"><h2 class="card-title text-amber-900">Reason for correction</h2></div>
                <div class="p-5"><x-field label="Reason" name="correction_reason" type="textarea" rows="2" required /></div>
            </div>
        @else
            <div class="card">
                <div class="card-header"><h2 class="card-title">Attachments (optional)</h2></div>
                <div class="p-5">
                    <input type="file" name="attachments[]" multiple class="input file:mr-3 file:rounded file:border-0 file:bg-navy-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-navy-800">
                    <p class="hint">Copy of the outgoing document. PDF, images, Word or Excel; up to 20 MB each.</p>
                </div>
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ $editing ? route('outgoing.show', $record) : route('outgoing.index') }}" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save correction' : 'Register' }}</button>
        </div>
    </form>
</x-layouts.app>
